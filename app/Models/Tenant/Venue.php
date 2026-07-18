<?php

namespace App\Models\Tenant;

use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Menu\Menu;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\VenueSettings;
use App\Models\User;
use App\Models\UserVenue;
use App\Services\VenueModuleCache;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Venue extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'corporation_id',
        'name',
        'tax_id',
        'phone',
        'whatsapp_agent',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'timezone',
        'active',
        'require_table',
        'require_tab',
        'require_location',
        'call_waiter_header_url',
        'call_waiter_passphrase',
        'call_waiter_slug',
        'evolution_api_url',
        'evolution_api_key',
        'evolution_api_instance',
        'logo_url',
        'latitude',
        'longitude',
        'require_geolocation',
    ];

    protected $hidden = [
        'call_waiter_passphrase',
        'evolution_api_key',
        'evolution_api_instance',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'require_table' => 'boolean',
            'require_tab' => 'boolean',
            'require_location' => 'boolean',
            'require_geolocation' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_venue')
            ->using(UserVenue::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(VenueSettings::class);
    }

    public function kitchenStations(): HasMany
    {
        return $this->hasMany(KitchenStation::class);
    }

    public function preparationStatuses(): HasMany
    {
        return $this->hasMany(PreparationStatus::class)->orderBy('sort_order');
    }

    public function initialStatus(): HasOne
    {
        return $this->hasOne(PreparationStatus::class)->where('is_initial', true);
    }

    public function finalStatus(): HasOne
    {
        return $this->hasOne(PreparationStatus::class)->where('is_final', true);
    }

    public function serviceLocations(): HasMany
    {
        return $this->hasMany(ServiceLocation::class);
    }

    public function attendanceChannels(): HasMany
    {
        return $this->hasMany(AttendanceChannel::class)->orderBy('sort_order');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(VenueSubscription::class)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial, SubscriptionStatus::PastDue])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->latest('started_at');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(VenueModule::class);
    }

    public function activeModules(): array
    {
        return VenueModuleCache::remember($this, function (): array {
            return $this->modules()
                ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
                ->where(function ($query): void {
                    $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
                })
                ->pluck('module_code')
                ->all();
        });
    }

    public function activeModuleCodes(): Collection
    {
        return collect($this->activeModules());
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(VenueUsageRecord::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
