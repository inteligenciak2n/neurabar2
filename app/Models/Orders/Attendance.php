<?php

namespace App\Models\Orders;

use App\Concerns\BelongsToVenue;
use App\Enums\AttendanceStatus;
use App\Models\Concerns\HasOperationalConnection;
use App\Models\Payment\Payment;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'service_location_id',
        'attendance_channel_id',
        'tab_id',
        'customer_identifier',
        'code',
        'status',
        'party_size',
        'notes',
        'created_by',
        'claimed_by_user_id',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'closed_at' => 'datetime',
            'status' => AttendanceStatus::class,
        ];
    }

    /** Printed tab card codes are compared case-insensitively, so they are stored trimmed and upper-cased. */
    public static function normalizeCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : $code;
    }

    protected function code(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => self::normalizeCode($value));
    }

    public function attendanceChannel(): BelongsTo
    {
        return $this->belongsTo(AttendanceChannel::class);
    }

    public function tab(): BelongsTo
    {
        return $this->belongsTo(Tab::class);
    }

    public function serviceLocation(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', AttendanceStatus::Open);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', AttendanceStatus::Closed);
    }
}
