@extends('layouts.app')

@section('title', 'Edit Order #'.str_pad($order->id, 5, '0', STR_PAD_LEFT))
@section('page-title', 'Edit Order')
@section('page-subtitle', 'Update order #'.str_pad($order->id, 5, '0', STR_PAD_LEFT))

@section('topbar-actions')
<a href="{{ route('orders.show', $order->id) }}" class="btn btn-ghost btn-sm">← Back to Order</a>
@endsection

@push('styles')
<style>
    .order-item-row .remove-btn {
        background: none;
        border: none;
        color: var(--red);
        font-size: 16px;
        cursor: pointer;
        opacity: .7;
        padding: 0;
    }

    .order-item-row .remove-btn:hover {
        opacity: 1;
    }

    .item-num {
        font-family: var(--font-mono);
        font-size: 10px;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .06em;
    }
</style>
@endpush

@section('content')

@if($order->status !== 'pending')
<div class="alert alert-error">
    ✕ Order #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} cannot be edited —
    status is <strong>{{ $order->status }}</strong>. Only pending orders can be updated.
</div>
<a href="{{ route('orders.show', $order->id) }}" class="btn btn-ghost">← Back to Order</a>
@else

<form method="POST" action="{{ route('orders.update', $order->id) }}" id="orderForm">
    @csrf
    @method('PUT')

    <div class="create-grid">

        {{-- Left: Items --}}
        <div>
            <div class="card mb-16">
                <div class="card-header">
                    <div>
                        <h3>Order Items</h3>
                        <p>Editing order #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} · Changes are atomic</p>
                    </div>
                </div>

                <div class="card-body" style="display:flex;flex-direction:column;gap:12px;" id="itemsContainer">

                    @php
                    $existingItems = old('items')
                    ? collect(old('items'))->map(fn($i) => (object)$i)
                    : $order->orderItems;
                    @endphp

                    @foreach($existingItems as $i => $item)
                    @php
                    $nameProductId = 'items[' . $i . '][product_id]';
                    $nameQuantity = 'items[' . $i . '][quantity]';
                    $oldProductId = old('items.' . $i . '.product_id', $item->product_id ?? '');
                    $oldQty = old('items.' . $i . '.quantity', $item->quantity ?? 1);
                    @endphp
                    <div class="order-item-row" id="item-{{ $i }}">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <span class="item-num">Item {{ $i + 1 }}</span>
                            <button type="button" class="remove-btn" onclick="removeItem({{ $i }})">✕</button>
                        </div>
                        <div class="item-grid">
                            <div>
                                <select name="{{ $nameProductId }}" onchange="updatePreview({{ $i }})">
                                    <option value="">Select product...</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                        data-stock="{{ $product->stock }}" {{ $oldProductId==$product->id ? 'selected' :
                                        '' }}>
                                        {{ $product->name }} (Stock: {{ $product->stock }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('items.'.$i.'.product_id')
                                <div class="error-msg">⚠ {{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <input type="number" name="{{ $nameQuantity }}" value="{{ $oldQty }}" min="1" max="1000"
                                    placeholder="Qty" onchange="updatePreview({{ $i }})" />
                                @error('items.'.$i.'.quantity')
                                <div class="error-msg">⚠ {{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="item-preview" id="preview-{{ $i }}" style="display:flex;">
                            <span>Unit price: <span class="mono accent" id="price-{{ $i }}">—</span></span>
                            <span>Subtotal: <span class="mono green" id="subtotal-{{ $i }}">—</span></span>
                        </div>
                    </div>
                    @endforeach

                </div>

                <div style="padding:0 20px 20px;">
                    <button type="button" class="add-item-btn" id="addItemBtn" onclick="addItem()">
                        <span style="font-size:18px;color:var(--accent);">⊕</span> Add Another Product
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <label>Order Notes (optional)</label>
                    <textarea name="notes" rows="3"
                        placeholder="Special instructions...">{{ old('notes', $order->notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Right: Summary --}}
        <div class="order-summary">
            <div class="card mb-12">
                <div class="card-header">
                    <h3>Update Summary</h3>
                </div>
                <div class="card-body">
                    <div
                        style="background:var(--amber-dim);border:1px solid rgba(245,166,35,.2);border-radius:8px;padding:10px 12px;margin-bottom:12px;font-size:12px;color:var(--amber);">
                        ⚠ Updating restores old stock then deducts new quantities atomically
                    </div>
                    <div class="summary-row"><span>Products</span><span class="mono" id="summaryCount">0</span></div>
                    <div class="summary-row"><span>Total qty</span><span class="mono" id="summaryQty">0</span></div>
                    <div class="summary-divider"></div>
                    <div class="summary-total">
                        <span class="summary-total-label">New Total</span>
                        <span class="summary-total-value" id="summaryTotal">—</span>
                    </div>
                </div>
            </div>
            <div id="lowStockWarning" class="alert alert-warning mb-12" style="display:none;">⚠ Some products have low
                stock (≤ 10 units)</div>
            <div id="outStockWarning" class="alert alert-error mb-12" style="display:none;">✕ Product out of stock</div>
            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">✓ Update Order</button>
            <div class="text-xs muted" style="text-align:center;margin-top:8px;">Changes are wrapped in a DB transaction
            </div>
        </div>

    </div>
</form>
@endif

@endsection

@push('scripts')
@php
/*
* Pre-build the JS products array in PHP so @json stays on ONE line.
* Multi-line @json( with array syntax [ ] crashes Blade because Blade
* sees the [ as an unclosed PHP array bracket.
*/
$jsProducts = $products->map(fn($p) => [
'id' => $p->id,
'price' => (float) $p->price,
'stock' => (int) $p->stock,
'name' => $p->name,
])->values()->toArray();

$jsItemCount = old('items') ? count(old('items')) : $order->orderItems->count();
@endphp

<script>
    const PRODUCTS  = {!! json_encode($jsProducts) !!};
let   itemCount = {{ $jsItemCount }};
</script>

@verbatim
<script>
    function fmtINR(n) {
    return '₹' + new Intl.NumberFormat('en-IN').format(Math.round(n));
}

function updatePreview(index) {
    var row      = document.getElementById('item-' + index);
    if (!row) return;
    var sel      = row.querySelector('select');
    var qty      = parseInt(row.querySelector('input[type=number]').value) || 0;
    var priceEl  = document.getElementById('price-'    + index);
    var subEl    = document.getElementById('subtotal-' + index);
    var prevEl   = document.getElementById('preview-'  + index);

    if (sel && sel.value && priceEl) {
        var opt   = sel.options[sel.selectedIndex];
        var price = parseFloat(opt.dataset.price) || 0;
        priceEl.textContent = fmtINR(price);
        subEl.textContent   = fmtINR(price * qty);
        if (prevEl) prevEl.style.display = 'flex';
    }
    recalculateSummary();
}

function recalculateSummary() {
    var total = 0, count = 0, qty = 0, hasLow = false, hasOut = false;
    document.querySelectorAll('.order-item-row').forEach(function(row) {
        var sel  = row.querySelector('select');
        var qtyI = row.querySelector('input[type=number]');
        if (!sel || !sel.value) return;
        var opt   = sel.options[sel.selectedIndex];
        var price = parseFloat(opt.dataset.price) || 0;
        var stock = parseInt(opt.dataset.stock)   || 0;
        var q     = parseInt(qtyI ? qtyI.value : 0) || 0;
        total += price * q; count++; qty += q;
        if (stock === 0) hasOut = true;
        else if (stock <= 10) hasLow = true;
    });
    document.getElementById('summaryCount').textContent = count;
    document.getElementById('summaryQty').textContent   = qty;
    document.getElementById('summaryTotal').textContent = total > 0 ? fmtINR(total) : '—';
    document.getElementById('lowStockWarning').style.display = hasLow ? 'flex' : 'none';
    document.getElementById('outStockWarning').style.display = hasOut ? 'flex' : 'none';
}

function addItem() {
    var all = document.querySelectorAll('.order-item-row');
    if (all.length >= 20) return;
    var index  = itemCount++;
    var prefix = 'items[' + index + ']';
    var opts   = PRODUCTS.map(function(p) {
        return '<option value="' + p.id
            + '" data-price="' + p.price
            + '" data-stock="' + p.stock + '">'
            + (p.name || 'Product #' + p.id)
            + ' (Stock: ' + p.stock + ')</option>';
    }).join('');

    var html = '<div class="order-item-row" id="item-' + index + '">'
        + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">'
        + '<span class="item-num">Item ' + (all.length + 1) + '</span>'
        + '<button type="button" class="remove-btn" onclick="removeItem(' + index + ')">✕</button>'
        + '</div><div class="item-grid">'
        + '<div><select name="' + prefix + '[product_id]" onchange="updatePreview(' + index + ')">'
        + '<option value="">Select product...</option>' + opts + '</select></div>'
        + '<div><input type="number" name="' + prefix + '[quantity]"'
        + ' value="1" min="1" max="1000" onchange="updatePreview(' + index + ')"/></div>'
        + '</div>'
        + '<div class="item-preview" id="preview-' + index + '" style="display:none;">'
        + '<span>Unit price: <span class="mono accent" id="price-' + index + '">—</span></span>'
        + '<span>Subtotal: <span class="mono green" id="subtotal-' + index + '">—</span></span>'
        + '</div></div>';

    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
    recalculateSummary();
}

function removeItem(index) {
    var row = document.getElementById('item-' + index);
    if (row) { row.remove(); recalculateSummary(); }
}

document.getElementById('orderForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner"></span> Updating...';
    btn.disabled  = true;
});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.order-item-row').forEach(function(row) {
        var id = parseInt(row.id.replace('item-', ''));
        updatePreview(id);
    });
});
</script>
@endverbatim
@endpush