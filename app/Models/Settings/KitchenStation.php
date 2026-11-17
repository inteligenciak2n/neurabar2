<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Enums\KitchenStationCode;
use App\Models\Concerns\HasOperationalConnection;
use App\Models\Menu\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenStation extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'name',
        'code',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'code' => KitchenStationCode::class,
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isWaiterCaller(): bool
    {
        return $this->code === KitchenStationCode::WaiterCaller;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeAssignableToProducts(Builder $query): Builder
    {
        return $query->whereNull('code');
    }
}
