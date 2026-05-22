<?php

namespace App\Models\Menu;

use App\Concerns\BelongsToVenue;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combo extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'name',
        'description',
        'price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class);
    }
}
