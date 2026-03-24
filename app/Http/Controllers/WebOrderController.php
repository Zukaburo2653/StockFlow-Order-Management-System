<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\OrderStatusService;
use App\Exceptions\OrderException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebOrderController extends Controller
{
    public function __construct(
        private readonly OrderService       $orderService,
        private readonly OrderStatusService $statusService,
    ) {}

    public function index(Request $request)
    {
        $query = Order::with(['orderItems'])
            ->where('user_id', Auth::id())
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        $orders       = $query->paginate(15)->withQueryString();
        $statusCounts = Order::where('user_id', Auth::id())
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('orders.index', compact('orders', 'statusCounts'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('orders.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'              => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:1000'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->orderService->createOrder(
                userId: Auth::id(),
                items:  $request->input('items'),
                meta:   $request->only('notes'),
            );

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', "Order #" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " placed successfully.");

        } catch (OrderException $e) {
            return back()->withInput()->withErrors(['order' => $e->getMessage()]);
        }
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);
        $order->load(['orderItems.product']);

        $availableActions = $this->statusService->getAvailableActions($order);
        $timeline         = $this->statusService->getStatusTimeline($order);

        return view('orders.show', compact('order', 'availableActions', 'timeline'));
    }

    public function edit(Order $order)
    {
        $this->authorizeOrder($order);

        if (!$order->isEditable()) {
            return redirect()
                ->route('orders.show', $order->id)
                ->with('error', "Only pending orders can be edited.");
        }

        $order->load(['orderItems.product']);

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('orders.edit', compact('order', 'products'));
    }

    public function update(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        $request->validate([
            'items'              => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:1000'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $updated = $this->orderService->updateOrder(
                order: $order,
                items: $request->input('items'),
                meta:  $request->only('notes'),
            );

            return redirect()
                ->route('orders.show', $updated->id)
                ->with('success', "Order #" . str_pad($updated->id, 5, '0', STR_PAD_LEFT) . " updated successfully.");

        } catch (OrderException $e) {
            return back()->withInput()->withErrors(['order' => $e->getMessage()]);
        }
    }

    // ── PENDING → PROCESSING ──────────────────────────────────────────────────
    public function confirm(Order $order)
    {
        $this->authorizeOrder($order);

        try {
            $this->statusService->confirmOrder($order);

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', "Order #" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " confirmed — now Processing.");

        } catch (OrderException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }
    }

    // ── PROCESSING → COMPLETED ────────────────────────────────────────────────
    public function complete(Order $order)
    {
        $this->authorizeOrder($order);

        try {
            $this->statusService->completeOrder($order);

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', "Order #" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " marked as Completed.");

        } catch (OrderException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }
    }

    // ── PENDING → CANCELLED ───────────────────────────────────────────────────
    public function cancel(Order $order)
    {
        $this->authorizeOrder($order);

        try {
            $this->statusService->cancelOrder($order, $this->orderService);

            return redirect()
                ->route('orders.index')
                ->with('success', "Order #" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " cancelled. Stock restored.");

        } catch (OrderException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }
    }

    private function authorizeOrder(Order $order): void
    {
        if ($order->user_id !== Auth::id()) {
            abort(404);
        }
    }
}