<?php

namespace App\Models\Tenant;

use App\Enums\ModuleStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueModule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'module_code', 'status', 'quantity', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'status' => ModuleStatus::class,
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
