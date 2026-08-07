<?php

namespace App\Models\Tenant;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Observers\SubscriptionStatusObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy(SubscriptionStatusObserver::class)]
class CorporationSubscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    /**
     * Motivo da próxima mudança de status, consumido pelo observer de
     * histórico. Não é persistido na assinatura.
     */
    public ?string $statusChangeReason = null;

    protected $fillable = [
        'corporation_id', 'plan_catalog_id', 'affiliate_code_id', 'billing_mode',
        'status', 'billing_day', 'grace_period_days', 'started_at', 'ended_at', 'trial_ends_at', 'currency',
        'terms_accepted_at', 'gateway', 'gateway_customer_id', 'gateway_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'billing_day' => 'integer',
            'grace_period_days' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'billing_mode' => BillingMode::class,
            'status' => SubscriptionStatus::class,
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    public function planCatalog(): BelongsTo
    {
        return $this->belongsTo(PlanCatalog::class);
    }

    public function statusHistory(): MorphMany
    {
        return $this->morphMany(SubscriptionStatusHistory::class, 'subscription');
    }

    public function isBilledByGateway(): bool
    {
        return $this->gateway_subscription_id !== null;
    }
}
