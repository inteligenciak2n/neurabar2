<?php

namespace App\Models\Orders;

use App\Models\Concerns\HasOperationalConnection;
use App\Models\Menu\ModifierOption;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'order_item_id',
        'modifier_option_id',
        'extra_price_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'extra_price_snapshot' => 'decimal:2',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function modifierOption(): BelongsTo
    {
        return $this->belongsTo(ModifierOption::class);
    }
}
