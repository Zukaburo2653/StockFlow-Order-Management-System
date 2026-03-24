@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')
@section('page-subtitle', 'Manage and track all customer orders')

@section('topbar-actions')
<a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">⊕ New Order</a>
@endsection

@section('content')

{{-- ── Status Summary Cards (clickable filter) ───────────────────────── --}}
@php
$statusCards = [
'pending' => ['color' => 'var(--amber)', 'label' => 'Pending'],
'processing' => ['color' => 'var(--accent)', 'label' => 'Processing'],
'completed' => ['color' => 'var(--green)', 'label' => 'Completed'],
'cancelled' => ['color' => 'var(--red)', 'label' => 'Cancelled'],
];
@endphp
<div class="grid-4 mb-20">
    @foreach($statusCards as $status => $cfg)
    <a href="{{ route('orders.index', ['status' => $status]) }}" class="card stat-card"
        style="{{ request('status') == $status ? 'border-color:'.$cfg['color'].';' : '' }} text-decoration:none;padding:14px 18px;cursor:pointer;">
        <div class="stat-label">{{ $cfg['label'] }}</div>
        <div class="stat-value" style="font-size:26px;color:{{ $cfg['color'] }}">
            {{ number_format($statusCounts[$status] ?? 0) }}
        </div>
    </a>
    @endforeach
</div>

{{-- ── Table Card ──────────────────────────────────────────────────────── --}}
<div class="card">
    {{-- Filters --}}
    <form method="GET" action="{{ route('orders.index') }}" class="filters-bar">
        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search by order ID or user ID..." />
        </div>
        <select name="status" onchange="this.form.submit()" style="width:auto;">
            <option value="">All Status</option>
            @foreach(['pending','processing','completed','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status')==$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
        @if(request('search') || request('status'))
        <a href="{{ route('orders.index') }}" class="btn btn-ghost btn-sm">✕ Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>User</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $order->id) }}" class="mono accent text-xs"
                            style="text-decoration:none;">
                            #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td><span class="mid">User #{{ $order->user_id }}</span></td>
                    <td><span class="mid">{{ $order->orderItems->count() }} item{{ $order->orderItems->count() != 1 ?
                            's' : '' }}</span></td>
                    <td><span class="mono fw-500">₹{{ number_format($order->total_amount) }}</span></td>
                    <td><span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                    <td><span class="muted text-xs">{{ $order->created_at->format('d M Y') }}</span></td>
                    <td>
                        <div class="flex gap-6">
                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-ghost btn-sm">View</a>
                            @if($order->status === 'pending')
                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-ghost btn-sm">Edit</a>
                            <form method="POST" action="{{ route('orders.cancel', $order->id) }}"
                                onsubmit="return confirm('Cancel this order and restore stock?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:50px;color:var(--slate);">
                        <div style="font-size:32px;margin-bottom:10px;">◈</div>
                        <div style="font-size:14px;color:var(--text-mid);">No orders found</div>
                        <div class="text-xs mt-8">Try adjusting your filters</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ number_format($orders->total())
            }}</span>
        <div class="pagination">
            {{-- Previous --}}
            @if($orders->onFirstPage())
            <button class="page-btn" disabled>‹</button>
            @else
            <a href="{{ $orders->previousPageUrl() }}" class="page-btn">‹</a>
            @endif

            {{-- Page numbers --}}
            @php
            $orderPageStart = max(1, $orders->currentPage() - 2);
            $orderPageEnd = min($orders->lastPage(), $orders->currentPage() + 2);
            $orderPageLinks = $orders->getUrlRange($orderPageStart, $orderPageEnd);
            @endphp
            @foreach($orderPageLinks as $page => $url)
            <a href="{{ $url }}" class="page-btn {{ $page == $orders->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach

            {{-- Next --}}
            @if($orders->hasMorePages())
            <a href="{{ $orders->nextPageUrl() }}" class="page-btn">›</a>
            @else
            <button class="page-btn" disabled>›</button>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection