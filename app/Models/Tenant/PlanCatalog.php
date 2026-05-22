<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanCatalog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'monthly_price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'monthly_price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function corporations(): HasMany
    {
        return $this->hasMany(Corporation::class);
    }
}
