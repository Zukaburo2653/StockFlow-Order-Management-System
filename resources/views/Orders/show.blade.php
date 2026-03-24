@extends('layouts.app')

@section('title', 'Order #'.str_pad($order->id, 5, '0', STR_PAD_LEFT))
@section('page-title', 'Order Detail')
@section('page-subtitle', 'Order #'.str_pad($order->id, 5, '0', STR_PAD_LEFT))

@section('topbar-actions')
<a href="{{ route('orders.index') }}" class="btn btn-ghost btn-sm">← Back to Orders</a>
@if($order->status === 'pending')
<a href="{{ route('orders.edit', $order->id) }}" class="btn btn-ghost btn-sm">✎ Edit</a>
@endif
@endsection

@push('styles')
<style>
    /* ── Status Timeline ─────────────────────────────────────────────────── */
    .timeline {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 24px;
    }

    .timeline-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        position: relative;
    }

    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 18px;
        left: calc(50% + 18px);
        right: calc(-50% + 18px);
        height: 2px;
        background: var(--border);
        z-index: 0;
    }

    .timeline-step.done::after {
        background: var(--green);
    }

    .timeline-step.active::after {
        background: var(--border);
    }

    .timeline-dot {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        z-index: 1;
        position: relative;
        border: 2px solid var(--border);
        background: var(--surface);
        color: var(--slate);
        transition: all .3s;
    }

    .timeline-step.done .timeline-dot {
        background: var(--green-dim);
        border-color: var(--green);
        color: var(--green);
    }

    .timeline-step.active .timeline-dot {
        background: var(--accent-glow);
        border-color: var(--accent);
        color: var(--accent);
        animation: pulse-ring 2s infinite;
    }

    .timeline-step.cancelled .timeline-dot {
        background: var(--red-dim);
        border-color: var(--red);
        color: var(--red);
    }

    .timeline-label {
        font-size: 11px;
        margin-top: 8px;
        color: var(--slate);
        text-align: center;
    }

    .timeline-step.done .timeline-label {
        color: var(--green);
        font-weight: 500;
    }

    .timeline-step.active .timeline-label {
        color: var(--accent);
        font-weight: 500;
    }

    .timeline-step.cancelled .timeline-label {
        color: var(--red);
        font-weight: 500;
    }

    @keyframes pulse-ring {
        0% {
            box-shadow: 0 0 0 0 rgba(108, 142, 245, .4);
        }

        70% {
            box-shadow: 0 0 0 8px rgba(108, 142, 245, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(108, 142, 245, 0);
        }
    }

    /* ── Action Buttons ──────────────────────────────────────────────────── */
    .action-section {
        padding: 20px;
    }

    .action-title {
        font-size: 11px;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 14px;
    }

    .action-grid {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .action-card {
        flex: 1;
        min-width: 200px;
        padding: 16px 18px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: var(--surface);
        cursor: pointer;
        transition: all .15s;
        text-align: left;
    }

    .action-card:hover {
        border-color: var(--border-hi);
        background: var(--card);
    }

    .action-card.success-action:hover {
        border-color: var(--green);
    }

    .action-card.danger-action:hover {
        border-color: var(--red);
    }

    .action-card-icon {
        font-size: 22px;
        margin-bottom: 8px;
    }

    .action-card-title {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .action-card-desc {
        font-size: 11px;
        color: var(--slate);
        line-height: 1.5;
    }

    .action-card.success-action .action-card-icon {
        color: var(--green);
    }

    .action-card.danger-action .action-card-icon {
        color: var(--red);
    }

    .action-card.success-action .action-card-title {
        color: var(--green);
    }

    .action-card.danger-action .action-card-title {
        color: var(--red);
    }

    /* ── Status final badge ──────────────────────────────────────────────── */
    .status-final {
        padding: 16px 20px;
        border-radius: 10px;
        text-align: center;
        font-size: 13px;
        font-weight: 500;
    }

    .status-final.completed {
        background: var(--green-dim);
        color: var(--green);
        border: 1px solid rgba(61, 214, 140, .2);
    }

    .status-final.cancelled {
        background: var(--red-dim);
        color: var(--red);
        border: 1px solid rgba(240, 82, 82, .2);
    }
</style>
@endpush

@section('content')
<div style="max-width:760px;">

    {{-- ── Status Timeline ──────────────────────────────────────────────── --}}
    <div class="card mb-16" style="padding:24px 28px;">
        <div class="timeline">
            @foreach($timeline as $step)
            <div class="timeline-step {{ $step['state'] }}">
                <div class="timeline-dot">{{ $step['icon'] }}</div>
                <div class="timeline-label">{{ $step['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Current status message --}}
        @php
        $statusMessages = [
        'pending' => ['color'=>'var(--amber)', 'msg'=>'Your order has been placed and is awaiting confirmation.'],
        'processing' => ['color'=>'var(--accent)', 'msg'=>'Your order is being processed and will be dispatched soon.'],
        'completed' => ['color'=>'var(--green)', 'msg'=>'Your order has been delivered successfully.'],
        'cancelled' => ['color'=>'var(--red)', 'msg'=>'This order was cancelled. Stock has been restored.'],
        ];
        $sm = $statusMessages[$order->status] ?? ['color'=>'var(--slate)','msg'=>''];
        @endphp
        <div style="text-align:center;font-size:13px;color:{{ $sm['color'] }};margin-top:4px;">
            {{ $sm['msg'] }}
        </div>
    </div>

    {{-- ── Order Info Card ──────────────────────────────────────────────── --}}
    <div class="card mb-16">
        <div class="card-header">
            <div>
                <div style="font-family:var(--font-display);font-size:22px;">
                    Order #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                </div>
                <p>{{ $order->created_at->format('d M Y') }} at {{ $order->created_at->format('h:i A') }}</p>
            </div>
            <div class="flex gap-8 align-items:center;">
                <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                @if($order->status === 'pending')
                <span class="badge badge-active" style="font-size:10px;">Editable</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="grid-2 mb-16" style="gap:10px;">
                @foreach([
                ['Order ID', '#'.str_pad($order->id, 5, '0', STR_PAD_LEFT)],
                ['User ID', 'User #'.$order->user_id],
                ['Total Amount', '₹'.number_format($order->total_amount)],
                ['Items', $order->orderItems->count().' item'.($order->orderItems->count()!=1?'s':'')],
                ] as [$label, $val])
                <div style="background:var(--surface);border-radius:8px;padding:12px 14px;">
                    <div class="text-xs muted" style="text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">
                        {{ $label }}</div>
                    <div class="fw-500 mono" style="font-size:15px;">{{ $val }}</div>
                </div>
                @endforeach
            </div>

            @if($order->notes)
            <div style="background:var(--surface);border-radius:8px;padding:12px 14px;">
                <div class="text-xs muted mb-4" style="text-transform:uppercase;letter-spacing:.05em;">Notes</div>
                <div class="text-sm mid">{{ $order->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Order Items ──────────────────────────────────────────────────── --}}
    <div class="card mb-16">
        <div class="card-header">
            <h3>Order Items</h3>
        </div>
        @foreach($order->orderItems as $item)
        <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);">
            <div
                style="width:36px;height:36px;background:var(--surface);border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;">
                ◇</div>
            <div style="flex:1;">
                <div class="fw-500">{{ $item->product->name ?? 'Product #'.$item->product_id }}</div>
                <div class="text-xs muted mt-4">
                    SKU: {{ $item->product->sku ?? 'N/A' }} &nbsp;·&nbsp;
                    Qty: {{ $item->quantity }} × ₹{{ number_format($item->unit_price) }}
                </div>
            </div>
            <div class="mono fw-500">₹{{ number_format($item->subtotal) }}</div>
        </div>
        @endforeach
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;">
            <span class="fw-500">Total Amount</span>
            <span style="font-family:var(--font-display);font-size:22px;color:var(--accent);">₹{{
                number_format($order->total_amount) }}</span>
        </div>
    </div>

    {{-- ── Status Actions ───────────────────────────────────────────────── --}}
    @if(count($availableActions) > 0)
    <div class="card">
        <div class="action-section">
            <div class="action-title">Available Actions</div>
            <div class="action-grid">
                @foreach($availableActions as $action)
                <form method="POST" action="{{ $action['action'] === 'cancel'
                        ? route('orders.cancel', $order->id)
                        : route('orders.'.$action['action'], $order->id) }}"
                    onsubmit="return confirm('{{ $action['confirm'] }}')">
                    @csrf
                    @if($action['action'] === 'cancel')
                    @method('DELETE')
                    @endif
                    <button type="submit"
                        class="action-card {{ $action['style'] === 'danger' ? 'danger-action' : 'success-action' }}">
                        <div class="action-card-icon">{{ $action['icon'] }}</div>
                        <div class="action-card-title">{{ $action['label'] }}</div>
                        <div class="action-card-desc">
                            @if($action['action'] === 'confirm')
                            Move order to Processing. Payment confirmed.
                            @elseif($action['action'] === 'complete')
                            Mark as delivered. Order will be closed.
                            @elseif($action['action'] === 'cancel')
                            Cancel order. Stock will be automatically restored.
                            @endif
                        </div>
                    </button>
                </form>
                @endforeach
            </div>
        </div>
    </div>

    @elseif($order->status === 'completed')
    <div class="status-final completed">
        ✓ This order has been completed and delivered. No further actions available.
    </div>

    @elseif($order->status === 'cancelled')
    <div class="status-final cancelled">
        ✕ This order was cancelled. Stock has been restored. No further actions available.
    </div>
    @endif

</div>
@endsection