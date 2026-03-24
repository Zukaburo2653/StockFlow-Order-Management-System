@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview of your order management system')

@section('content')

{{-- ── Stat Cards ─────────────────────────────────────────────────────── --}}
<div class="stats-grid mb-24">
    <div class="card stat-card">
        <div class="stat-icon">◈</div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value accent">{{ number_format($stats['total_orders']) }}</div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">◇</div>
        <div class="stat-label">Total Products</div>
        <div class="stat-value green">{{ number_format($stats['total_products']) }}</div>
        <div class="stat-sub">Active inventory</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">₹</div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value amber">₹{{ number_format($stats['total_revenue'] / 100000, 1) }}L</div>
        <div class="stat-sub">Across all orders</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-label">Pending Orders</div>
        <div class="stat-value red">{{ number_format($stats['pending_orders']) }}</div>
        <div class="stat-sub">Awaiting action</div>
    </div>
</div>

{{-- ── Charts Row ──────────────────────────────────────────────────────── --}}
<div class="grid-3 mb-24">

    {{-- Orders this week --}}
    <div class="card" style="padding:20px 22px;">
        <div class="text-xs muted" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Orders This
            Week</div>
        <div style="font-family:var(--font-display);font-size:24px;margin-bottom:2px;">
            {{ number_format($weeklyOrders['total']) }}
            <span class="text-sm green">↑ {{ $weeklyOrders['growth'] }}%</span>
        </div>
        <div class="mini-chart">
            @php $maxVal = max($weeklyOrders['data']); @endphp
            @foreach($weeklyOrders['data'] as $i => $val)
            <div class="mini-bar"
                style="height:{{ ($maxVal > 0 ? ($val/$maxVal)*100 : 0) }}%;background:{{ $loop->last ? 'var(--accent)' : 'rgba(108,142,245,.35)' }};">
            </div>
            @endforeach
        </div>
        <div class="chart-labels">
            @foreach(['M','T','W','T','F','S','S'] as $d)
            <span>{{ $d }}</span>
            @endforeach
        </div>
    </div>

    {{-- Revenue this week --}}
    <div class="card" style="padding:20px 22px;">
        <div class="text-xs muted" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Revenue This
            Week</div>
        <div style="font-family:var(--font-display);font-size:24px;margin-bottom:2px;">
            ₹{{ number_format($weeklyRevenue['total'] / 1000, 1) }}K
            <span class="text-sm green">↑ {{ $weeklyRevenue['growth'] }}%</span>
        </div>
        <div class="mini-chart">
            @php $maxRev = max($weeklyRevenue['data']); @endphp
            @foreach($weeklyRevenue['data'] as $val)
            <div class="mini-bar"
                style="height:{{ ($maxRev > 0 ? ($val/$maxRev)*100 : 0) }}%;background:{{ $loop->last ? 'var(--green)' : 'rgba(61,214,140,.35)' }};">
            </div>
            @endforeach
        </div>
        <div class="chart-labels">
            @foreach(['M','T','W','T','F','S','S'] as $d)
            <span>{{ $d }}</span>
            @endforeach
        </div>
    </div>

    {{-- Order Status Distribution --}}
    <div class="card" style="padding:20px 22px;">
        <div class="text-xs muted" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">Order
            Status</div>
        @foreach($statusDist as $s)
        <div class="mb-12">
            <div class="flex justify-between text-xs mb-4">
                <span class="mid">{{ ucfirst($s['status']) }}</span>
                <span class="mono" style="color:{{ $s['color'] }}">{{ $s['percent'] }}%</span>
            </div>
            <div class="progress-wrap">
                <div class="progress-bar" style="width:{{ $s['percent'] }}%;background:{{ $s['color'] }};"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Recent Orders ───────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <div>
            <h3>Recent Orders</h3>
            <p>Latest 10 transactions</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-ghost btn-sm">View All →</a>
    </div>
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
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                <tr onclick="window.location='{{ route('orders.show', $order->id) }}'" style="cursor:pointer;">
                    <td><span class="mono accent text-xs">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span></td>
                    <td><span class="mid">User #{{ $order->user_id }}</span></td>
                    <td><span class="mid">{{ $order->orderItems->count() }} item{{ $order->orderItems->count() != 1 ?
                            's' : '' }}</span></td>
                    <td><span class="mono fw-500">₹{{ number_format($order->total_amount) }}</span></td>
                    <td><span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                    <td><span class="muted text-xs">{{ $order->created_at->format('d M Y') }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--slate);">No orders yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection