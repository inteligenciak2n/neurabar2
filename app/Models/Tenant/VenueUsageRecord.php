<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueUsageRecord extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'module_code', 'period', 'quantity',
        'included_quantity', 'overage_quantity', 'tier_id',
        'base_calculated_price', 'overage_calculated_price', 'total_calculated_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'included_quantity' => 'integer',
            'overage_quantity' => 'integer',
            'base_calculated_price' => 'decimal:2',
            'overage_calculated_price' => 'decimal:2',
            'total_calculated_price' => 'decimal:2',
        ];
    }
}
