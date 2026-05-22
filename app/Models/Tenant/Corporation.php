<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Corporation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'tax_id',
        'email',
        'contact_phone',
        'plan_catalog_id',
        'plan_name',
        'subscription_value',
        'plan_start_date',
        'plan_end_date',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'plan_start_date' => 'date',
            'plan_end_date' => 'date',
            'active' => 'boolean',
            'subscription_value' => 'decimal:2',
        ];
    }

    public function planCatalog(): BelongsTo
    {
        return $this->belongsTo(PlanCatalog::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}
