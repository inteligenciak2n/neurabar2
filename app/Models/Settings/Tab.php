<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Models\Concerns\HasOperationalConnection;
use App\Models\Orders\Attendance;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tab extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'tab_series_id',
        'code',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Tab $tab): void {
            $tab->code = strtoupper(trim((string) $tab->code));
        });
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(TabSeries::class, 'tab_series_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
