<?php

namespace App\Models\Menu;

use App\Models\Concerns\HasOperationalConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'combo_id',
        'product_id',
        'variation_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
}
