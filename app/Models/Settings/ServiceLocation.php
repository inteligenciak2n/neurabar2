<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Enums\ServiceLocationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLocation extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'name',
        'type',
        'active',
        'default_attendance_channel_id',
        'qr_token',
    ];

    protected function casts(): array
    {
        return [
            'type' => ServiceLocationType::class,
            'active' => 'boolean',
        ];
    }

    public function defaultAttendanceChannel(): BelongsTo
    {
        return $this->belongsTo(AttendanceChannel::class, 'default_attendance_channel_id');
    }
}
