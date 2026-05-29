<?php

namespace App\Models\Orders;

use App\Concerns\BelongsToVenue;
use App\Enums\AttendanceStatus;
use App\Models\Payment\Payment;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'service_location_id',
        'attendance_channel_id',
        'customer_identifier',
        'status',
        'party_size',
        'notes',
        'created_by',
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

    public function attendanceChannel(): BelongsTo
    {
        return $this->belongsTo(AttendanceChannel::class);
    }

    public function serviceLocation(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
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
