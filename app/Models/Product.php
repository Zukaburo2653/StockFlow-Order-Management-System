<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'sku',
        'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'stock'     => 'integer',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only return products that are orderable:
     * active, not soft-deleted, and have stock.
     */
    public function scopeOrderable($query)
    {
        return $query->where('is_active', true)->where('stock', '>', 0);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function hasStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}
