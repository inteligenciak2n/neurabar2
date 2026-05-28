<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Enums\ServiceLocationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected function casts(): array
    {
        return [
            'type' => ServiceLocationType::class,
            'active' => 'boolean',
        ];
    }
}
