<?php

namespace App\Models\Tenant;

use App\Models\Menu\Menu;
use App\Models\Orders\Attendance;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\VenueSettings;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
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

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
