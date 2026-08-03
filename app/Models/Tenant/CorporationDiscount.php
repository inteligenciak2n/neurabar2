<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorporationDiscount extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $connection = 'saas';

    protected $fillable = [
        'corporation_id', 'type', 'value', 'description',
        'valid_from', 'valid_until', 'max_months', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            // Centavos quando `type = fixed`; pontos-base (1/100 de 1%) quando `type = percentage`.
            'value' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'max_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    public function scopeActiveForPeriod($query, string $period): void
    {
        $start = "{$period}-01";
        $end = now()->parse($start)->endOfMonth()->toDateString();

        $query
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', $end)
            ->where(function ($q) use ($start): void {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $start);
            });
    }
}
