<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Exceptions\OrderException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    // ─── GET /api/orders ──────────────────────────────────────────────────────

    /**
     * List orders for the authenticated user.
     * Eager loads orderItems + products to avoid N+1.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['orderItems.product'])
            ->where('user_id', Auth::id())
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => OrderResource::collection($orders->items()),
            'meta'    => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    // ─── POST /api/orders ─────────────────────────────────────────────────────

    /**
     * Create a new order.
     * Entire operation is wrapped in a DB transaction inside the service.
     * If ANY product fails → entire order is rolled back.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(
                userId: Auth::id(),
                items:  $request->input('items'),
                meta:   $request->only('notes'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data'    => new OrderResource($order),
            ], 201);

        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while creating the order.',
            ], 500);
        }
    }

    // ─── GET /api/orders/{order} ──────────────────────────────────────────────

    public function show(Order $order): JsonResponse
    {
        // Authorization: user can only view their own orders
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Eager load to prevent N+1 queries
        $order->load(['orderItems.product', 'user']);

        return response()->json([
            'success' => true,
            'data'    => new OrderResource($order),
        ]);
    }

    // ─── PUT /api/orders/{order} ──────────────────────────────────────────────

    /**
     * Update an order.
     * Only allowed when status = pending.
     * Validates product existence before update.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        try {
            $updated = $this->orderService->updateOrder(
                order: $order,
                items: $request->input('items'),
                meta:  $request->only('notes'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'data'    => new OrderResource($updated),
            ]);

        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while updating the order.',
            ], 500);
        }
    }

    // ─── DELETE /api/orders/{order} ───────────────────────────────────────────

    /**
     * Cancel an order and restore stock.
     */
    public function destroy(Order $order): JsonResponse
    {
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        try {
            $cancelled = $this->orderService->cancelOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully. Stock has been restored.',
                'data'    => new OrderResource($cancelled),
            ]);

        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while cancelling the order.',
            ], 500);
        }
    }
}
