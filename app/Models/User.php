<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'venue_id',
        'corporation_id',
        'pin',
        'active',
        'lang',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
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
            'role' => UserRole::class,
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    public function activeVenue() //: ?Venue
    {
        if (! $this->role?->isOperational()) {
            return null;
        }

        if (in_array($this->role, [UserRole::CorporationAdmin, UserRole::Owner], true)) {
            $activeVenueId = session('active_venue_id');

            if ($activeVenueId) {
                $venue = Venue::find($activeVenueId);

                if ($venue && $venue->corporation_id === $this->corporation_id) {
                    return $venue;
                }
            }

            if ($this->corporation_id) {
                return Venue::where('corporation_id', $this->corporation_id)
                    ->where('active', true)
                    ->first();
            }

            // Fallback: venue directly assigned (e.g. owner without a corporation)
            return $this->venue;
        }

        return $this->venue;
    }

    public function setSessionLanguage(): void
    {
        if ($this->lang && $this->lang !== app()->getLocale()) {
            session(['locale' => $this->lang]);
            app()->setLocale( $this->lang );
        }
    }
}
