<?php

namespace App\Models\Tenant;

use App\Enums\BillingMode;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Corporation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'owner_id',
        'affiliate_code_id',
        'name',
        'tax_id',
        'email',
        'contact_phone',
        'active',
        'self_connection',
        'is_dedicated',
        'billing_address_json',
        'billing_tax_regime',
        'billing_state_registration',
    ];

    protected $hidden = [
        'self_connection',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_dedicated' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CorporationSubscription::class)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial, SubscriptionStatus::PastDue])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->latest('started_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CorporationSubscription::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CorporationModule::class);
    }

    public function activeModules(): HasMany
    {
        return $this->modules()
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            });
    }

    public function hasActiveModule(ModuleCode $module): bool
    {
        return $this->activeModules()
            ->where('module_code', $module->value)
            ->exists();
    }

    public function isBillingUnified(): bool
    {
        return $this->subscription?->billing_mode === BillingMode::Unified;
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(CorporationDiscount::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(AffiliateCode::class, 'affiliate_code_id');
    }
}
