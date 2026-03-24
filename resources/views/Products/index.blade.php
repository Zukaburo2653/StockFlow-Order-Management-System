@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')
@section('page-subtitle', 'Browse inventory and monitor stock levels')

@section('content')

{{-- ── Summary Cards ───────────────────────────────────────────────────── --}}
<div class="grid-4 mb-20">
    <div class="card stat-card" style="padding:14px 18px;">
        <div class="stat-label">Total Products</div>
        <div class="stat-value accent" style="font-size:26px;">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="card stat-card" style="padding:14px 18px;">
        <div class="stat-label">Active</div>
        <div class="stat-value green" style="font-size:26px;">{{ number_format($stats['active']) }}</div>
    </div>
    <div class="card stat-card" style="padding:14px 18px;">
        <div class="stat-label">Low Stock (≤10)</div>
        <div class="stat-value amber" style="font-size:26px;">{{ number_format($stats['low_stock']) }}</div>
    </div>
    <div class="card stat-card" style="padding:14px 18px;">
        <div class="stat-label">Out of Stock</div>
        <div class="stat-value red" style="font-size:26px;">{{ number_format($stats['out_of_stock']) }}</div>
    </div>
</div>

{{-- ── Table Card ──────────────────────────────────────────────────────── --}}
<div class="card">

    {{-- Filters --}}
    <form method="GET" action="{{ route('products.index') }}" class="filters-bar">
        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or SKU..." />
        </div>

        @php
        $filterTabs = [
        '' => 'All',
        'active' => 'Active',
        'low' => 'Low Stock',
        'out' => 'Out of Stock',
        'inactive' => 'Inactive',
        ];
        $activeFilter = request('filter', '');
        @endphp

        <div class="filter-tabs">
            @foreach($filterTabs as $val => $label)
            <button type="submit" name="filter" value="{{ $val }}"
                class="filter-tab {{ $activeFilter === $val ? 'active' : '' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        @if(request('search'))
        <a href="{{ route('products.index', ['filter' => request('filter')]) }}" class="btn btn-ghost btn-sm">✕
            Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                @php
                $stockColor = $product->stock === 0
                ? 'var(--red)'
                : ($product->stock <= 10 ? 'var(--amber)' : 'var(--green)' ); $stockPct=min(($product->stock / 200) *
                    100, 100);
                    $stockBadge = $product->stock === 0 ? 'out' : ($product->stock <= 10 ? 'low' : '' ); @endphp <tr>
                        <td>
                            <div class="flex-center gap-10">
                                <div
                                    style="width:32px;height:32px;border-radius:8px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;">
                                    ◇</div>
                                <div>
                                    <div class="fw-500">{{ $product->name }}</div>
                                    <div class="text-xs muted mt-4">ID #{{ $product->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="mono text-xs muted">{{ $product->sku }}</span></td>
                        <td><span class="mono fw-500">₹{{ number_format($product->price) }}</span></td>
                        <td>
                            <div class="stock-wrap">
                                <div class="stock-bar">
                                    <div class="stock-fill"
                                        style="width:{{ $stockPct }}%;background:{{ $stockColor }};"></div>
                                </div>
                                <span class="mono text-sm" style="color:{{ $stockColor }};">{{ $product->stock }}</span>
                                @if($stockBadge)
                                <span class="badge badge-{{ $stockBadge }}" style="font-size:10px;">
                                    {{ $stockBadge === 'out' ? 'Out' : 'Low' }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-{{ $product->is_active ? 'active' : 'inactive' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:50px;color:var(--slate);">
                                <div style="font-size:32px;margin-bottom:10px;">◇</div>
                                <div style="font-size:14px;color:var(--text-mid);">No products found</div>
                                <div class="text-xs mt-8">Try adjusting your search or filters</div>
                            </td>
                        </tr>
                        @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    @php
    $pageStart = max(1, $products->currentPage() - 2);
    $pageEnd = min($products->lastPage(), $products->currentPage() + 2);
    $pageLinks = $products->getUrlRange($pageStart, $pageEnd);
    @endphp
    <div class="pagination-wrap">
        <span>Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ number_format($products->total())
            }}</span>
        <div class="pagination">
            @if($products->onFirstPage())
            <button class="page-btn" disabled>‹</button>
            @else
            <a href="{{ $products->previousPageUrl() }}" class="page-btn">‹</a>
            @endif

            @foreach($pageLinks as $pg => $url)
            <a href="{{ $url }}" class="page-btn {{ $pg == $products->currentPage() ? 'active' : '' }}">{{ $pg }}</a>
            @endforeach

            @if($products->hasMorePages())
            <a href="{{ $products->nextPageUrl() }}" class="page-btn">›</a>
            @else
            <button class="page-btn" disabled>›</button>
            @endif
        </div>
    </div>
    @endif

</div>

@endsection