<?php

namespace App\Models\Orders;

use App\Concerns\BelongsToVenue;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Models\Concerns\HasOperationalConnection;
use App\Models\Settings\ServiceLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'service_location_id',
        'attendance_id',
        'assigned_user_id',
        'type',
        'message',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ServiceRequestType::class,
            'status' => ServiceRequestStatus::class,
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function serviceLocation(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [ServiceRequestStatus::Pending, ServiceRequestStatus::Acknowledged]);
    }

    public function scopeOfType(Builder $query, ServiceRequestType $type): Builder
    {
        return $query->where('type', $type);
    }
}
