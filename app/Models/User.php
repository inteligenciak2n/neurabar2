<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\ModuleCode;
use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use App\Services\Billing\BillingStatusService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $connection = 'saas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_venue_id',
        'pin',
        'active',
        'lang',
        'profile',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'profile',
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'pin',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'profile' => ProfileEnum::class,
        ];
    }

    public function currentVenue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'current_venue_id');
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'user_venue')
            ->using(UserVenue::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedCorporation(): HasOne
    {
        return $this->hasOne(Corporation::class, 'owner_id');
    }

    public function activeVenue(): ?Venue
    {
        return $this->currentVenue;
    }

    public function currentVenueRole(): ?UserRole
    {
        if (! $this->current_venue_id) {
            return null;
        }

        $pivot = $this->venues()
            ->wherePivot('venue_id', $this->current_venue_id)
            ->first();

        if (! $pivot) {
            return null;
        }

        $role = $pivot->pivot->role;

        return $role instanceof UserRole ? $role : UserRole::tryFrom((string) $role);
    }

    public function setSessionLanguage(): void
    {
        if ($this->lang && $this->lang !== app()->getLocale()) {
            session(['locale' => $this->lang]);
            app()->setLocale($this->lang);
        }
    }

    public function canAccessModule(ModuleCode $module): bool
    {
        $venue = $this->currentVenue;

        if (! $venue) {
            return false;
        }

        if (! in_array($module->value, $venue->activeModules(), true)) {
            return false;
        }

        if (BillingStatusService::isBlocked($venue)) {
            return false;
        }

        return true;
    }
}
