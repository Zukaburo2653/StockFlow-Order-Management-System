<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Exceptions\OrderException;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    // ─── Create Order ─────────────────────────────────────────────────────────

    /**
     * Create an order wrapped in a DB transaction.
     * 
     * If ANY product fails (out of stock, deleted, invalid) → entire order is rolled back.
     * Uses lockForUpdate() to prevent race conditions / double-ordering.
     *
     * @param  int   $userId
     * @param  array $items   [['product_id' => 1, 'quantity' => 2], ...]
     * @param  array $meta    ['notes' => '...']
     * @return Order
     *
     * @throws OrderException
     */
    public function createOrder(int $userId, array $items, array $meta = []): Order
    {
        // ── Validate for duplicate product_ids before hitting DB ──────────────
        $this->ensureNoDuplicateProducts($items);

        return DB::transaction(function () use ($userId, $items, $meta) {
            // Create the order shell first
            $order = Order::create([
                'user_id'      => $userId,
                'status'       => Order::STATUS_PENDING,
                'total_amount' => 0,
                'notes'        => $meta['notes'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity  = (int) $item['quantity'];

                // ── CONCURRENCY FIX: Lock the row before reading stock ────────
                // withTrashed() is required so we can distinguish between
                // "product does not exist" and "product was soft-deleted".
                // Without it, soft-deleted rows are excluded by the global scope
                // and lockForUpdate()->find() returns null for both cases,
                // making the trashed() check unreachable.
                $product = Product::withTrashed()->lockForUpdate()->find($productId);

                // ── Edge Case: Invalid product_id ────────────────────────────
                if (!$product) {
                    throw new OrderException(
                        "Product with ID {$productId} does not exist.",
                        422
                    );
                }

                // ── Edge Case: Product soft-deleted ───────────────────────────
                if ($product->trashed()) {
                    throw new OrderException(
                        "Product '{$product->name}' has been removed and cannot be ordered.",
                        422
                    );
                }

                // ── Edge Case: Product inactive ───────────────────────────────
                if (!$product->is_active) {
                    throw new OrderException(
                        "Product '{$product->name}' is currently unavailable.",
                        422
                    );
                }

                // ── Edge Case: Stock = 0 ──────────────────────────────────────
                // Use <= 0 (not === 0) — the integer cast is reliable but
                // defensive comparison avoids surprises if stock is ever negative.
                if ($product->stock <= 0) {
                    throw new OrderException(
                        "Product '{$product->name}' is out of stock.",
                        422
                    );
                }

                // ── Edge Case: Not enough stock ───────────────────────────────
                if (!$product->hasStock($quantity)) {
                    throw new InsufficientStockException(
                        $product->name,
                        $quantity,
                        $product->stock
                    );
                }

                // ── Deduct stock atomically ───────────────────────────────────
                $updated = DB::table('products')
                    ->where('id', $productId)
                    ->where('stock', '>=', $quantity)
                    ->update([
                        'stock'      => DB::raw("stock - {$quantity}"),
                        'updated_at' => now(),
                    ]);

                if ($updated === 0) {
                    throw new InsufficientStockException($product->name, $quantity, 0);
                }

                // ── Create order item ─────────────────────────────────────────
                $unitPrice = (float) $product->price;
                $subtotal  = round($unitPrice * $quantity, 2);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            // ── Update total on the order ──────────────────────────────────────
            $order->update(['total_amount' => round($totalAmount, 2)]);

            // Eager load for response (avoids N+1)
            return $order->load(['orderItems.product', 'user']);
        });
    }

    // ─── Update Order ─────────────────────────────────────────────────────────

    /**
     * Update order items. Only allowed when status = pending.
     * Validates product existence and stock before updating.
     *
     * @throws OrderException
     */
    public function updateOrder(Order $order, array $items, array $meta = []): Order
    {
        // ── Prevent editing completed/cancelled orders ─────────────────────────
        if (!$order->isEditable()) {
            throw new OrderException(
                "Order #{$order->id} cannot be modified because its status is '{$order->status}'. "
                . "Only pending orders can be updated.",
                422
            );
        }

        $this->ensureNoDuplicateProducts($items);

        return DB::transaction(function () use ($order, $items, $meta) {
            // Restore stock for existing items before recalculating
            $this->restoreStockForOrder($order);

            // Remove existing items
            $order->orderItems()->delete();

            $totalAmount = 0;

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity  = (int) $item['quantity'];

                // withTrashed() so we can distinguish deleted vs non-existent
                $product = Product::withTrashed()->lockForUpdate()->find($productId);

                if (!$product) {
                    throw new OrderException("Product with ID {$productId} does not exist.", 422);
                }

                if ($product->trashed()) {
                    throw new OrderException(
                        "Product '{$product->name}' has been removed and cannot be added.",
                        422
                    );
                }

                if (!$product->is_active) {
                    throw new OrderException("Product '{$product->name}' is unavailable.", 422);
                }

                if ($product->stock <= 0) {
                    throw new OrderException("Product '{$product->name}' is out of stock.", 422);
                }

                if (!$product->hasStock($quantity)) {
                    throw new InsufficientStockException($product->name, $quantity, $product->stock);
                }

                $updated = DB::table('products')
                    ->where('id', $productId)
                    ->where('stock', '>=', $quantity)
                    ->update([
                        'stock'      => DB::raw("stock - {$quantity}"),
                        'updated_at' => now(),
                    ]);

                if ($updated === 0) {
                    throw new InsufficientStockException($product->name, $quantity, 0);
                }

                $unitPrice = (float) $product->price;
                $subtotal  = round($unitPrice * $quantity, 2);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $order->update([
                'total_amount' => round($totalAmount, 2),
                'notes'        => $meta['notes'] ?? $order->notes,
            ]);

            return $order->fresh(['orderItems.product', 'user']);
        });
    }

    // ─── Cancel Order ─────────────────────────────────────────────────────────

    /**
     * Cancel a pending order and restore stock.
     *
     * @throws OrderException
     */
    public function cancelOrder(Order $order): Order
    {
        if (!$order->isPending()) {
            throw new OrderException(
                "Only pending orders can be cancelled. Current status: '{$order->status}'.",
                422
            );
        }

        return DB::transaction(function () use ($order) {
            $this->restoreStockForOrder($order);

            $order->update(['status' => Order::STATUS_CANCELLED]);

            Log::info("Order #{$order->id} cancelled. Stock restored.", [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
            ]);

            return $order->fresh(['orderItems.product']);
        });
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Restore product stock for all items in an order.
     * Used when cancelling or updating an order.
     */
    private function restoreStockForOrder(Order $order): void
    {
        $order->load('orderItems');

        foreach ($order->orderItems as $item) {
            DB::table('products')
                ->where('id', $item->product_id)
                ->update([
                    'stock'      => DB::raw("stock + {$item->quantity}"),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Ensure no product appears twice in the same order request.
     *
     * @throws OrderException
     */
    private function ensureNoDuplicateProducts(array $items): void
    {
        $productIds = array_column($items, 'product_id');

        // array_count_values counts occurrences of each value.
        // Filter to only those that appear more than once.
        $duplicates = array_keys(
            array_filter(
                array_count_values($productIds),
                fn (int $count) => $count > 1
            )
        );

        if (!empty($duplicates)) {
            throw new OrderException(
                'Duplicate products in order. Each product can only appear once. '
                . 'Duplicate product ID(s): ' . implode(', ', $duplicates),
                422
            );
        }
    }
}