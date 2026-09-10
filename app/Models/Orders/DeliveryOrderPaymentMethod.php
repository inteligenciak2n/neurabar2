<?php

namespace App\Models\Orders;

use App\Enums\PaymentMethod;
use App\Models\Concerns\HasOperationalConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment method(s) the guest selected at checkout, pending until the delivery
 * order is marked as Delivered (see AdvanceDeliveryOrderStatusAction), when the
 * actual Payment/PaymentItem records are created.
 */
class DeliveryOrderPaymentMethod extends Model
{
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'delivery_order_id',
        'method',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}
