<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreparationStatus extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'name',
        'color',
        'sort_order',
        'show_to_customer',
        'is_final',
        'is_initial',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'show_to_customer' => 'boolean',
            'is_final' => 'boolean',
            'is_initial' => 'boolean',
        ];
    }
}
