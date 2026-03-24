<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderService::class);
        $this->user    = User::factory()->create();
    }

    private function product(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'is_active' => true], $attrs));
    }

    // ─── createOrder ──────────────────────────────────────────────────────────

    public function test_create_order_deducts_stock(): void
    {
        $p = $this->product(['stock' => 10, 'price' => 50.00]);

        $order = $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 3],
        ]);

        $this->assertEquals(7, $p->fresh()->stock);
        $this->assertEquals(150.00, (float) $order->total_amount);
        $this->assertEquals('pending', $order->status);
    }

    public function test_throws_when_product_not_found(): void
    {
        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('does not exist');

        $this->service->createOrder($this->user->id, [
            ['product_id' => 99999, 'quantity' => 1],
        ]);
    }

    public function test_throws_when_stock_is_zero(): void
    {
        $p = $this->product(['stock' => 0]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('out of stock');

        $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 1],
        ]);
    }

    public function test_throws_insufficient_stock_exception(): void
    {
        $p = $this->product(['stock' => 2]);

        $this->expectException(InsufficientStockException::class);

        $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 5],
        ]);
    }

    public function test_throws_for_duplicate_product_ids(): void
    {
        $p = $this->product(['stock' => 50]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('Duplicate products');

        $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 1],
            ['product_id' => $p->id, 'quantity' => 2],
        ]);
    }

    public function test_throws_for_soft_deleted_product(): void
    {
        $p = $this->product(['stock' => 10]);
        $p->delete();

        $this->expectException(OrderException::class);

        $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 1],
        ]);
    }

    public function test_throws_for_inactive_product(): void
    {
        $p = $this->product(['stock' => 10, 'is_active' => false]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('unavailable');

        $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 1],
        ]);
    }

    // ─── cancelOrder ──────────────────────────────────────────────────────────

    public function test_cancel_restores_stock(): void
    {
        $p     = $this->product(['stock' => 10]);
        $order = $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 4],
        ]);

        $this->assertEquals(6, $p->fresh()->stock);

        $this->service->cancelOrder($order);

        $this->assertEquals(10, $p->fresh()->stock);
        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    public function test_cannot_cancel_completed_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'completed',
        ]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('Only pending orders can be cancelled');

        $this->service->cancelOrder($order);
    }

    // ─── updateOrder ──────────────────────────────────────────────────────────

    public function test_update_restores_old_stock_and_deducts_new(): void
    {
        $p = $this->product(['stock' => 10, 'price' => 20.00]);

        $order = $this->service->createOrder($this->user->id, [
            ['product_id' => $p->id, 'quantity' => 3], // deduct 3
        ]);

        $this->assertEquals(7, $p->fresh()->stock);

        // Update to quantity = 5
        $this->service->updateOrder($order, [
            ['product_id' => $p->id, 'quantity' => 5],
        ]);

        // Old 3 restored, new 5 deducted → 10 - 5 = 5
        $this->assertEquals(5, $p->fresh()->stock);
        $this->assertEquals(100.00, (float) $order->fresh()->total_amount);
    }

    public function test_cannot_update_completed_order(): void
    {
        $p     = $this->product();
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'completed',
        ]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage("cannot be modified");

        $this->service->updateOrder($order, [
            ['product_id' => $p->id, 'quantity' => 1],
        ]);
    }
}
