<?php

namespace App\Models\Orders;

use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use App\Models\Settings\PreparationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'quantity',
        'unit_price',
        'notes',
        'preparation_status_id',
        'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'ready_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function preparationStatus(): BelongsTo
    {
        return $this->belongsTo(PreparationStatus::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class);
    }

    public function scopeInPreparation(Builder $query): Builder
    {
        return $query->whereNull('ready_at');
    }
}
