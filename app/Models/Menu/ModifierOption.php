<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModifierOption extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'modifier_group_id',
        'name',
        'extra_price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'extra_price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }
}
