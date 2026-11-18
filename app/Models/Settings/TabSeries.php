<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Models\Concerns\HasOperationalConnection;
use App\Support\TabCode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TabSeries extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'prefix',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (TabSeries $series): void {
            $series->prefix = TabCode::normalizePrefix($series->prefix);
        });
    }

    public function tabs(): HasMany
    {
        return $this->hasMany(Tab::class);
    }
}
