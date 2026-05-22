<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueSettings extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'cover_charge',
        'service_fee_percent',
        'table_count',
    ];

    protected function casts(): array
    {
        return [
            'cover_charge' => 'decimal:2',
            'service_fee_percent' => 'decimal:2',
            'table_count' => 'integer',
        ];
    }
}
