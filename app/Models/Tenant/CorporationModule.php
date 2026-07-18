<?php

namespace App\Models\Tenant;

use App\Enums\ModuleStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporationModule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $table = 'corporation_modules';

    protected $fillable = [
        'corporation_id', 'module_code', 'status',
        'custom_monthly_price', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_monthly_price' => 'decimal:2',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'status' => ModuleStatus::class,
        ];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ModuleCatalog::class, 'module_code', 'code');
    }
}
