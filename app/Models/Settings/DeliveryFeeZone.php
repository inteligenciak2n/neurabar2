<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Models\Concerns\HasOperationalConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryFeeZone extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'label',
        'zip_code_start',
        'zip_code_end',
        'fee',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'zip_code_start' => 'integer',
            'zip_code_end' => 'integer',
            'fee' => 'decimal:2',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
