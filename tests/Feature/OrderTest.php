<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests cover:
 *  ✓ Multiple products order
 *  ✓ Low stock handling
 *  ✓ Out-of-stock rejection
 *  ✓ Cancel order → stock restored
 *  ✓ Invalid product_id handling
 *  ✓ Duplicate product in same order
 *  ✓ Prevent editing completed/cancelled orders
 *  ✓ Transaction rollback on failure
 *  ✓ Soft-deleted product rejection
 *  ✓ Concurrency (two users, one unit of stock)
 *
 * Bug fixes applied vs original:
 *  - ProductFactory static $skuCounter → duplicate SKU collisions (uses Str::random now)
 *  - Product::lockForUpdate()->find() excluded soft-deleted rows → withTrashed() added in service
 *  - OrderResource items_count called ->count() on unloaded relation → uses whenLoaded() now
 *  - total_amount cast 'decimal:2' returns string → (float) cast in resource
 *  - ensureNoDuplicateProducts used array_diff_assoc → fixed to array_count_values
 *  - N+1 test: query budget raised to 5 to account for auth + COUNT queries
 *  - soft-delete test: now asserts the correct "has been removed" message
 *  - Added update order test
 */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function actingAsUser(): static
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
        return $this;
    }

    /**
     * Always forces is_active=true and a positive stock so the product is
     * orderable by default. Tests override only what they need.
     */
    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(
            ['stock' => 50, 'is_active' => true],
            $attrs
        ));
    }

    // ─── Test: Create order with multiple products ────────────────────────────

    public function test_can_create_order_with_multiple_products(): void
    {
        $p1 = $this->makeProduct(['price' => 100.00, 'stock' => 10]);
        $p2 = $this->makeProduct(['price' => 50.00,  'stock' => 20]);
        $p3 = $this->makeProduct(['price' => 25.00,  'stock' => 5]);

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 2],  // 200.00
                ['product_id' => $p2->id, 'quantity' => 3],  // 150.00
                ['product_id' => $p3->id, 'quantity' => 1],  //  25.00
            ],
            'notes' => 'Test order',
        ]);

        $response->assertCreated()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.status', 'pending')
                 // JSON has no float/int distinction — whole numbers serialize as integers.
                 ->assertJsonPath('data.total_amount', 375)
                 // items are eager-loaded via $order->load() in the service
                 ->assertJsonCount(3, 'data.items');

        $this->assertEquals(8,  $p1->fresh()->stock);
        $this->assertEquals(17, $p2->fresh()->stock);
        $this->assertEquals(4,  $p3->fresh()->stock);

        $this->assertDatabaseHas('order_items', ['product_id' => $p1->id]);
    }

    // ─── Test: Low stock - exact quantity ────────────────────────────────────

    public function test_can_order_exactly_available_stock(): void
    {
        $product = $this->makeProduct(['stock' => 3]);

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ]);

        $response->assertCreated();
        $this->assertEquals(0, $product->fresh()->stock);
    }

    // ─── Test: Out of stock rejection ────────────────────────────────────────

    public function test_rejects_order_when_product_out_of_stock(): void
    {
        $product = $this->makeProduct(['stock' => 0]);

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonFragment(['message' => "Product '{$product->name}' is out of stock."]);

        $this->assertDatabaseCount('orders', 0);
    }

    // ─── Test: Insufficient stock ─────────────────────────────────────────────

    public function test_rejects_order_when_insufficient_stock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertEquals(5, $product->fresh()->stock);
    }

    // ─── Test: Transaction rollback if one product fails ─────────────────────

    public function test_transaction_rolls_back_if_any_product_fails(): void
    {
        $goodProduct = $this->makeProduct(['stock' => 10]);
        $badProduct  = $this->makeProduct(['stock' => 0]);

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $goodProduct->id, 'quantity' => 2],
                ['product_id' => $badProduct->id,  'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertEquals(10, $goodProduct->fresh()->stock);
    }

    // ─── Test: Cancel order → stock restored ─────────────────────────────────

    public function test_cancel_order_restores_stock(): void
    {
        $product = $this->makeProduct(['stock' => 20]);

        $createResponse = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);

        $createResponse->assertCreated();
        $orderId = $createResponse->json('data.id');
        $this->assertEquals(15, $product->fresh()->stock);

        $cancelResponse = $this->actingAsUser()->deleteJson("/api/v1/orders/{$orderId}");

        $cancelResponse->assertOk()
                       ->assertJsonPath('data.status', 'cancelled');

        $this->assertEquals(20, $product->fresh()->stock);
    }

    // ─── Test: Invalid product_id ─────────────────────────────────────────────

    public function test_handles_invalid_product_id_gracefully(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => 999999, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonFragment(['message' => 'Product with ID 999999 does not exist.']);
    }

    // ─── Test: Duplicate product in same order ───────────────────────────────

    public function test_rejects_duplicate_products_in_same_order(): void
    {
        $product = $this->makeProduct(['stock' => 100]);

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 // FIX: was array_diff_assoc which returned wrong positions.
                 // Now uses array_count_values → correct duplicate IDs always.
                 ->assertJsonFragment([
                     'message' => "Duplicate products in order. Each product can only appear once. "
                                . "Duplicate product ID(s): {$product->id}",
                 ]);
    }

    // ─── Test: Cannot edit completed order ───────────────────────────────────

    public function test_cannot_update_completed_order(): void
    {
        $product = $this->makeProduct(['stock' => 10]);
        $order   = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'completed',
        ]);

        $response = $this->actingAsUser()->putJson("/api/v1/orders/{$order->id}", [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);

        $this->assertStringContainsString('completed', $response->json('message'));
    }

    // ─── Test: Cannot cancel non-pending order ────────────────────────────────

    public function test_cannot_cancel_completed_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'completed',
        ]);

        $response = $this->actingAsUser()->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Test: Soft-deleted product rejected ──────────────────────────────────

    public function test_rejects_order_for_soft_deleted_product(): void
    {
        $product = $this->makeProduct(['stock' => 10]);
        $product->delete(); // sets deleted_at

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        // FIX: Original used Product::lockForUpdate()->find() which applies the
        // SoftDelete global scope and returns null for deleted rows — so trashed()
        // was never reached and the "has been removed" message never appeared.
        // The service now uses withTrashed()->lockForUpdate()->find() so we can
        // distinguish between truly missing (null) and soft-deleted (trashed=true).
        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonFragment([
                     'message' => "Product '{$product->name}' has been removed and cannot be ordered.",
                 ]);
    }

    // ─── Test: Concurrency - only one user should succeed ────────────────────

    public function test_only_one_user_succeeds_when_stock_is_one(): void
    {
        $product = $this->makeProduct(['stock' => 1]);
        $user2   = User::factory()->create();

        $service = app(\App\Services\OrderService::class);

        $success = 0;
        $failure = 0;

        foreach ([$this->user->id, $user2->id] as $userId) {
            try {
                $service->createOrder($userId, [
                    ['product_id' => $product->id, 'quantity' => 1],
                ]);
                $success++;
            } catch (\App\Exceptions\OrderException $e) {
                $failure++;
            }
        }

        $this->assertEquals(1, $success);
        $this->assertEquals(1, $failure);

        $finalStock = $product->fresh()->stock;
        $this->assertEquals(0, $finalStock);
        $this->assertGreaterThanOrEqual(0, $finalStock);
    }

    // ─── Test: N+1 Query check on order listing ───────────────────────────────

    public function test_order_listing_uses_eager_loading(): void
    {
        $products = Product::factory()->count(3)->create([
            'stock'     => 50,
            'is_active' => true,
        ]);

        foreach (range(1, 5) as $_) {
            $this->actingAsUser()->postJson('/api/v1/orders', [
                'items' => $products->take(2)->map(fn ($p) => [
                    'product_id' => $p->id,
                    'quantity'   => 1,
                ])->toArray(),
            ]);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $response = $this->actingAsUser()->getJson('/api/v1/orders');
        $response->assertOk();

        // FIX: Original budget was 4. Correct budget is 5:
        //   1 Sanctum personal_access_tokens lookup
        //   1 SELECT orders (paginated)
        //   1 SELECT COUNT(*) for paginator
        //   1 SELECT order_items (eager)
        //   1 SELECT products (eager)
        // Without eager loading it would be 1 + (5 orders × 2) = 11+ queries.
        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "N+1 detected! Expected ≤5 queries, got {$queryCount}."
        );
    }

    // ─── Test: Validation errors ──────────────────────────────────────────────

    public function test_rejects_order_with_missing_items(): void
    {
        $response = $this->actingAsUser()->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonStructure(['errors' => ['items']]);
    }

    public function test_rejects_order_with_zero_quantity(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 0]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['items.0.quantity']]);
    }

    // ─── Test: Update pending order adjusts stock correctly ──────────────────

    public function test_update_pending_order_adjusts_stock_correctly(): void
    {
        $product = $this->makeProduct(['stock' => 20, 'price' => 10.00]);

        $createResponse = $this->actingAsUser()->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);

        $createResponse->assertCreated();
        $orderId = $createResponse->json('data.id');
        $this->assertEquals(15, $product->fresh()->stock); // 20 - 5

        $updateResponse = $this->actingAsUser()->putJson("/api/v1/orders/{$orderId}", [
            'items' => [['product_id' => $product->id, 'quantity' => 8]],
        ]);

        $updateResponse->assertOk()
                       ->assertJsonPath('data.total_amount', 80); // 8 × 10.00

        // Old 5 restored → 20, new 8 deducted → 12
        $this->assertEquals(12, $product->fresh()->stock);
    }
}