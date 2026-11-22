<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_category_id', 'sku', 'name', 'unit', 'opening_stock', 'current_stock', 'reorder_level',
        'purchase_cost', 'selling_price', 'is_sellable', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'opening_stock' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'purchase_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_sellable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    public function isLowStock(): bool
    {
        return bccomp($this->current_stock, $this->reorder_level, 3) <= 0;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereColumn('current_stock', '<=', 'reorder_level');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('sku', 'like', "%{$term}%"));
    }
}
