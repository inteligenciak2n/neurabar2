<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Models\Concerns\HasOperationalConnection;
use App\Models\Orders\Attendance;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceChannel extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'name',
        'is_trackable',
        'requires_customer_identifier',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_trackable' => 'boolean',
            'requires_customer_identifier' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
