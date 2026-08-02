<?php

namespace App\Models\Tenant;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueSubscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'corporation_subscription_id', 'plan_catalog_id', 'affiliate_code_id',
        'status', 'base_value', 'modules_value', 'metered_value', 'dedicated_surcharge', 'total_value',
        'started_at', 'ended_at', 'trial_ends_at', 'gateway', 'gateway_customer_id', 'gateway_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'base_value' => 'decimal:2',
            'modules_value' => 'decimal:2',
            'metered_value' => 'decimal:2',
            'dedicated_surcharge' => 'decimal:2',
            'total_value' => 'decimal:2',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'status' => SubscriptionStatus::class,
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function corporationSubscription(): BelongsTo
    {
        return $this->belongsTo(CorporationSubscription::class);
    }

    public function isBilledByGateway(): bool
    {
        return $this->gateway_subscription_id !== null;
    }
}
