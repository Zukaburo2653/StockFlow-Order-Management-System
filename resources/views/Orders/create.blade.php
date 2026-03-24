@extends('layouts.app')

@section('title', 'New Order')
@section('page-title', 'New Order')
@section('page-subtitle', 'Create a transactional order with real-time stock validation')

@section('topbar-actions')
<a href="{{ route('orders.index') }}" class="btn btn-ghost btn-sm">← Back</a>
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

<form method="POST" action="{{ route('orders.store') }}" id="orderForm">
    @csrf

    <div class="create-grid">

        {{-- ── Left: Items ──────────────────────────────────────────────── --}}
        <div>
            <div class="card mb-16">
                <div class="card-header">
                    <div>
                        <h3>Order Items</h3>
                        <p>Add up to 20 products · Stock validated server-side</p>
                    </div>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:12px;" id="itemsContainer">

                    @if(old('items'))
                    @foreach(old('items') as $i => $oldItem)
                    @php
                    $nameProductId = 'items[' . $i . '][product_id]';
                    $nameQuantity = 'items[' . $i . '][quantity]';
                    $oldProductId = old('items.' . $i . '.product_id');
                    $oldQty = old('items.' . $i . '.quantity', 1);
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
                        <div class="item-preview" id="preview-{{ $i }}" style="display:none;">
                            <span>Unit price: <span class="mono accent" id="price-{{ $i }}">—</span></span>
                            <span>Subtotal: <span class="mono green" id="subtotal-{{ $i }}">—</span></span>
                        </div>
                    </div>
                    @endforeach
                    @else
                    {{-- Default first row --}}
                    <div class="order-item-row" id="item-0">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <span class="item-num">Item 1</span>
                        </div>
                        <div class="item-grid">
                            <div>
                                <select name="items[0][product_id]" onchange="updatePreview(0)">
                                    <option value="">Select product...</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                        data-stock="{{ $product->stock }}">
                                        {{ $product->name }} (Stock: {{ $product->stock }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <input type="number" name="items[0][quantity]" value="1" min="1" max="1000"
                                    placeholder="Qty" onchange="updatePreview(0)" />
                            </div>
                        </div>
                        <div class="item-preview" id="preview-0" style="display:none;">
                            <span>Unit price: <span class="mono accent" id="price-0">—</span></span>
                            <span>Subtotal: <span class="mono green" id="subtotal-0">—</span></span>
                        </div>
                    </div>
                    @endif

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
                        placeholder="Any special instructions for this order...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Right: Summary ───────────────────────────────────────────── --}}
        <div class="order-summary">
            <div class="card mb-12">
                <div class="card-header">
                    <h3>Order Summary</h3>
                </div>
                <div class="card-body">
                    <div class="summary-row">
                        <span>Products selected</span>
                        <span class="mono" id="summaryCount">0</span>
                    </div>
                    <div class="summary-row">
                        <span>Total quantity</span>
                        <span class="mono" id="summaryQty">0</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-total">
                        <span class="summary-total-label">Est. Total</span>
                        <span class="summary-total-value" id="summaryTotal">—</span>
                    </div>
                </div>
            </div>

            <div id="lowStockWarning" class="alert alert-warning mb-12" style="display:none;">
                ⚠ Some products have low stock (≤ 10 units)
            </div>
            <div id="outStockWarning" class="alert alert-error mb-12" style="display:none;">
                ✕ One or more selected products is out of stock
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                ⊕ Place Order
            </button>
            <div class="text-xs muted" style="text-align:center;margin-top:8px;">
                Transaction-safe · Stock locked on submit
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
{{--
IMPORTANT: @verbatim prevents Blade from parsing {{ }} and [[ ]] inside
the JS. We output the dynamic PHP values BEFORE @verbatim as JS variables,
then reference those variables inside the @verbatim block.
--}}
<script>
    // ── PHP → JS bridge (Blade processes these lines only) ───────────────────
const PRODUCTS  = @json($products->map(fn($p) => ['id'=>$p->id,'price'=>$p->price,'stock'=>$p->stock,'name'=>$p->name]));
let   itemCount = {{ old('items') ? count(old('items')) : 1 }};
</script>

@verbatim
<script>
    // ── Format INR ────────────────────────────────────────────────────────────
function fmtINR(n) {
    return '₹' + new Intl.NumberFormat('en-IN').format(Math.round(n));
}

// ── Update item preview ───────────────────────────────────────────────────
function updatePreview(index) {
    const row = document.getElementById('item-' + index);
    if (!row) return;
    const sel        = row.querySelector('select');
    const qty        = parseInt(row.querySelector('input[type=number]').value) || 0;
    const priceEl    = document.getElementById('price-'    + index);
    const subtotalEl = document.getElementById('subtotal-' + index);
    const previewEl  = document.getElementById('preview-'  + index);

    if (sel && sel.value && priceEl) {
        const opt   = sel.options[sel.selectedIndex];
        const price = parseFloat(opt.dataset.price) || 0;
        priceEl.textContent    = fmtINR(price);
        subtotalEl.textContent = fmtINR(price * qty);
        previewEl.style.display = 'flex';
    } else {
        if (previewEl) previewEl.style.display = 'none';
    }
    recalculateSummary();
}

// ── Recalculate order summary ─────────────────────────────────────────────
function recalculateSummary() {
    let total = 0, count = 0, qty = 0, hasLow = false, hasOut = false;

    document.querySelectorAll('.order-item-row').forEach(function(row) {
        const sel      = row.querySelector('select');
        const qtyInput = row.querySelector('input[type=number]');
        if (!sel || !sel.value) return;
        const opt   = sel.options[sel.selectedIndex];
        const price = parseFloat(opt.dataset.price) || 0;
        const stock = parseInt(opt.dataset.stock)   || 0;
        const q     = parseInt(qtyInput ? qtyInput.value : 0) || 0;
        total += price * q;
        count++;
        qty += q;
        if (stock === 0)       hasOut = true;
        else if (stock <= 10)  hasLow = true;
    });

    document.getElementById('summaryCount').textContent = count;
    document.getElementById('summaryQty').textContent   = qty;
    document.getElementById('summaryTotal').textContent = total > 0 ? fmtINR(total) : '—';
    document.getElementById('lowStockWarning').style.display = hasLow ? 'flex' : 'none';
    document.getElementById('outStockWarning').style.display = hasOut ? 'flex' : 'none';
}

// ── Add new item row ──────────────────────────────────────────────────────
function addItem() {
    const allRows = document.querySelectorAll('.order-item-row');
    if (allRows.length >= 20) {
        document.getElementById('addItemBtn').textContent = 'Maximum 20 items reached';
        return;
    }

    const index   = itemCount++;
    const options = PRODUCTS.map(function(p) {
        return '<option value="' + p.id + '" data-price="' + p.price + '" data-stock="' + p.stock + '">'
            + p.name + ' (Stock: ' + p.stock + ')</option>';
    }).join('');

    // Note: square brackets in name attributes are safe inside a JS string —
    // they are NOT parsed by Blade because this whole block is @verbatim.
    const namePrefix = 'items[' + index + ']';

    const html = '<div class="order-item-row" id="item-' + index + '">'
        + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">'
        + '<span class="item-num">Item ' + (allRows.length + 1) + '</span>'
        + '<button type="button" class="remove-btn" onclick="removeItem(' + index + ')">✕</button>'
        + '</div>'
        + '<div class="item-grid">'
        + '<div><select name="' + namePrefix + '[product_id]" onchange="updatePreview(' + index + ')">'
        + '<option value="">Select product...</option>' + options
        + '</select></div>'
        + '<div><input type="number" name="' + namePrefix + '[quantity]" '
        + 'value="1" min="1" max="1000" placeholder="Qty" onchange="updatePreview(' + index + ')"/></div>'
        + '</div>'
        + '<div class="item-preview" id="preview-' + index + '" style="display:none;">'
        + '<span>Unit price: <span class="mono accent" id="price-' + index + '">—</span></span>'
        + '<span>Subtotal: <span class="mono green" id="subtotal-' + index + '">—</span></span>'
        + '</div></div>';

    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
    recalculateSummary();
}

// ── Remove item row ───────────────────────────────────────────────────────
function removeItem(index) {
    const row = document.getElementById('item-' + index);
    if (row) { row.remove(); recalculateSummary(); }
}

// ── Submit loading state ──────────────────────────────────────────────────
document.getElementById('orderForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner"></span> Placing Order...';
    btn.disabled  = true;
});

// ── Init previews on page load (for old() re-population) ─────────────────
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.order-item-row').forEach(function(row) {
        const id = parseInt(row.id.replace('item-', ''));
        updatePreview(id);
    });
});
</script>
@endverbatim
@endpush