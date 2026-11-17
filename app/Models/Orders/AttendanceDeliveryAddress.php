<?php

namespace App\Models\Orders;

use App\Models\Concerns\HasOperationalConnection;
use App\Models\Settings\DeliveryFeeZone;
use App\Support\DeliveryAddressFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDeliveryAddress extends Model
{
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'attendance_id',
        'venue_customer_address_id',
        'delivery_fee_zone_id',
        'delivery_fee',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'reference_point',
        'delivery_phone',
    ];

    protected $appends = [
        'line',
        'maps_url',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function savedAddress(): BelongsTo
    {
        return $this->belongsTo(VenueCustomerAddress::class, 'venue_customer_address_id');
    }

    public function deliveryFeeZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryFeeZone::class);
    }

    protected function line(): Attribute
    {
        return Attribute::get(fn (): string => DeliveryAddressFormatter::line($this));
    }

    protected function mapsUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => DeliveryAddressFormatter::mapsUrl($this));
    }
}
