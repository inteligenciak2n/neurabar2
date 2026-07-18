<?php

namespace App\Models\Tenant;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporationSubscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'corporation_id', 'plan_catalog_id', 'affiliate_code_id', 'billing_mode',
        'status', 'billing_day', 'grace_period_days', 'started_at', 'ended_at', 'trial_ends_at', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'billing_day' => 'integer',
            'grace_period_days' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'billing_mode' => BillingMode::class,
            'status' => SubscriptionStatus::class,
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }
}
