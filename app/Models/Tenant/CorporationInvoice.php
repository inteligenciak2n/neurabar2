<?php

namespace App\Models\Tenant;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorporationInvoice extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $connection = 'saas';

    protected $fillable = [
        'corporation_id', 'corporation_subscription_id', 'affiliate_code_id',
        'period', 'due_date', 'status', 'is_finalized',
        'base_value', 'modules_value', 'metered_value', 'dedicated_surcharge', 'discount_value', 'total_value',
        'gateway_payment_id', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_finalized' => 'boolean',
            'status' => InvoiceStatus::class,
            'base_value' => 'integer',
            'modules_value' => 'integer',
            'metered_value' => 'integer',
            'dedicated_surcharge' => 'integer',
            'discount_value' => 'integer',
            'total_value' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    public function venueInvoices(): HasMany
    {
        return $this->hasMany(VenueInvoice::class);
    }
}
