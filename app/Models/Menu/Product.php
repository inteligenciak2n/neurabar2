<?php

namespace App\Models\Menu;

use App\Models\Concerns\HasOperationalConnection;
use App\Models\Settings\KitchenStation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'category_id',
        'kitchen_station_id',
        'name',
        'description',
        'servings',
        'price',
        'image_url',
        'active',
        'available_for_delivery',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'servings' => 'integer',
            'active' => 'boolean',
            'available_for_delivery' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function kitchenStation(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_group');
    }

    public function storedImagePath(): ?string
    {
        $value = $this->getRawOriginal('image_url');

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return null;
        }

        return $value;
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (?string $value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
                return $value;
            }

            return Storage::disk('public')->url($value);
        });
    }
}
