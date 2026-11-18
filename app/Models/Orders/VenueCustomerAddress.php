<?php

namespace App\Models\Orders;

use App\Concerns\BelongsToVenue;
use App\Models\Concerns\HasOperationalConnection;
use App\Models\Settings\DeliveryFeeZone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueCustomerAddress extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'venue_customer_id',
        'zip_code',
        'street',
        'number',
        'complement',
        'delivery_fee_zone_id',
        'neighborhood',
        'city',
        'state',
        'reference_point',
        'delivery_phone',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(VenueCustomer::class, 'venue_customer_id');
    }

    public function deliveryFeeZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryFeeZone::class);
    }
}
