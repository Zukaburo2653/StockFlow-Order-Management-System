<?php

namespace App\Services;

use App\Models\Order;
use App\Exceptions\OrderException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderStatusService
 *
 * Handles ALL automatic status transitions.
 * No manual status changes — every transition has a business reason.
 *
 * Allowed transitions:
 *   PENDING     → PROCESSING  (confirm order / payment done)
 *   PROCESSING  → COMPLETED   (mark as dispatched/delivered)
 *   PENDING     → CANCELLED   (user cancels before processing)
 *
 * Blocked transitions:
 *   PROCESSING  → CANCELLED   ❌ (already packed)
 *   COMPLETED   → anything    ❌ (final state)
 *   CANCELLED   → anything    ❌ (final state)
 *   PROCESSING  → PENDING     ❌ (no going back)
 */
class OrderStatusService
{
    // ─── Allowed transition map ───────────────────────────────────────────────
    private const TRANSITIONS = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['completed'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    // ─── Status labels for messages ───────────────────────────────────────────
    private const LABELS = [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
    ];

    // ─── PENDING → PROCESSING ─────────────────────────────────────────────────

    /**
     * Confirm order: PENDING → PROCESSING
     * Triggered when: payment confirmed / order accepted by seller
     */
    public function confirmOrder(Order $order): Order
    {
        $this->ensureTransitionAllowed($order, 'processing');

        return DB::transaction(function () use ($order) {
            $order->update([
                'status'     => Order::STATUS_PROCESSING,
                'updated_at' => now(),
            ]);

            Log::info("Order #{$order->id} confirmed → PROCESSING", [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
            ]);

            return $order->fresh();
        });
    }

    // ─── PROCESSING → COMPLETED ───────────────────────────────────────────────

    /**
     * Complete order: PROCESSING → COMPLETED
     * Triggered when: item dispatched / delivered to customer
     */
    public function completeOrder(Order $order): Order
    {
        $this->ensureTransitionAllowed($order, 'completed');

        return DB::transaction(function () use ($order) {
            $order->update([
                'status'     => Order::STATUS_COMPLETED,
                'updated_at' => now(),
            ]);

            Log::info("Order #{$order->id} completed → COMPLETED", [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
            ]);

            return $order->fresh();
        });
    }

    // ─── PENDING → CANCELLED ──────────────────────────────────────────────────

    /**
     * Cancel order: PENDING → CANCELLED
     * Only pending orders can be cancelled.
     * Stock is automatically restored.
     * Delegates to OrderService which handles stock restoration.
     */
    public function cancelOrder(Order $order, OrderService $orderService): Order
    {
        $this->ensureTransitionAllowed($order, 'cancelled');
        return $orderService->cancelOrder($order);
    }

    // ─── Transition validator ──────────────────────────────────────────────────

    /**
     * Validate that a status transition is allowed.
     * Throws OrderException with a clear message if not.
     */
    public function ensureTransitionAllowed(Order $order, string $toStatus): void
    {
        $from    = $order->status;
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (!in_array($toStatus, $allowed)) {
            $fromLabel = self::LABELS[$from]     ?? ucfirst($from);
            $toLabel   = self::LABELS[$toStatus] ?? ucfirst($toStatus);

            // Specific user-friendly messages for each blocked transition
            $message = match(true) {
                $from === 'completed' =>
                    "Order #{$order->id} is already completed and cannot be changed.",

                $from === 'cancelled' =>
                    "Order #{$order->id} is already cancelled and cannot be changed.",

                $from === 'processing' && $toStatus === 'cancelled' =>
                    "Order #{$order->id} is being processed and can no longer be cancelled. Contact support.",

                default =>
                    "Order #{$order->id} cannot move from '{$fromLabel}' to '{$toLabel}'.",
            };

            throw new OrderException($message, 422);
        }
    }

    // ─── Get next available actions for an order ──────────────────────────────

    /**
     * Returns what actions are available for an order's current status.
     * Used by the UI to show/hide action buttons.
     */
    public function getAvailableActions(Order $order): array
    {
        return match($order->status) {
            'pending' => [
                [
                    'action'  => 'confirm',
                    'label'   => 'Confirm Order',
                    'icon'    => '✓',
                    'style'   => 'success',
                    'confirm' => 'Confirm this order? It will move to Processing.',
                ],
                [
                    'action'  => 'cancel',
                    'label'   => 'Cancel Order',
                    'icon'    => '✕',
                    'style'   => 'danger',
                    'confirm' => 'Cancel this order? Stock will be restored.',
                ],
            ],
            'processing' => [
                [
                    'action'  => 'complete',
                    'label'   => 'Mark as Completed',
                    'icon'    => '✓',
                    'style'   => 'success',
                    'confirm' => 'Mark this order as completed/delivered?',
                ],
            ],
            'completed', 'cancelled' => [],
            default => [],
        };
    }

    // ─── Status timeline for UI display ──────────────────────────────────────

    /**
     * Returns the status timeline showing progress.
     * Used by the order detail page to display a visual progress bar.
     */
    public function getStatusTimeline(Order $order): array
    {
        $isCancelled = $order->status === 'cancelled';

        if ($isCancelled) {
            return [
                ['status' => 'pending',    'label' => 'Order Placed',  'icon' => '◈', 'state' => 'done'],
                ['status' => 'cancelled',  'label' => 'Cancelled',     'icon' => '✕', 'state' => 'cancelled'],
            ];
        }

        $steps = ['pending', 'processing', 'completed'];
        $currentIndex = array_search($order->status, $steps);

        return array_map(function ($step, $index) use ($currentIndex) {
            return [
                'status' => $step,
                'label'  => match($step) {
                    'pending'    => 'Order Placed',
                    'processing' => 'Processing',
                    'completed'  => 'Delivered',
                    default      => ucfirst($step),
                },
                'icon'  => match($step) {
                    'pending'    => '◈',
                    'processing' => '⟳',
                    'completed'  => '✓',
                    default      => '○',
                },
                'state' => match(true) {
                    $index < $currentIndex  => 'done',
                    $index === $currentIndex => 'active',
                    default                 => 'upcoming',
                },
            ];
        }, $steps, array_keys($steps));
    }
}