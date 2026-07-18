<?php

namespace App\Models\Tenant;

use App\Enums\ModuleBillingType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleCatalog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'code', 'name', 'description', 'category', 'billing_type',
        'base_monthly_price', 'unit_of_measure', 'dependencies',
        'required_roles', 'icon', 'sort_order', 'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'base_monthly_price' => 'decimal:2',
            'sort_order' => 'integer',
            'dependencies' => 'array',
            'required_roles' => 'array',
            'billing_type' => ModuleBillingType::class,
        ];
    }
}
