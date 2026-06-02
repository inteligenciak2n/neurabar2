<?php

namespace App\Models\Menu;

use App\Concerns\BelongsToVenue;
use App\Models\Concerns\HasOperationalConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierGroup extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'name',
        'required',
        'multiple_selection',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'multiple_selection' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(ModifierOption::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_group');
    }
}
