<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\PlanModuleUsageTierFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanModuleUsageTier extends Model
{
    /** @use HasFactory<PlanModuleUsageTierFactory> */
    use HasFactory;

    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'plan_catalog_version_id', 'module_code', 'min_quantity', 'max_quantity',
        'included_quantity', 'price_per_unit', 'flat_price',
        'overage_price_per_unit', 'overage_flat_fee', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'included_quantity' => 'integer',
            'price_per_unit' => 'integer',
            'flat_price' => 'integer',
            'overage_price_per_unit' => 'integer',
            'overage_flat_fee' => 'integer',
        ];
    }

    public function planCatalogVersion(): BelongsTo
    {
        return $this->belongsTo(PlanCatalogVersion::class);
    }
}
