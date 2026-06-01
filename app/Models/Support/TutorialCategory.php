<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorialCategory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'support';

    protected $table = 'support_tutorial_categories';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'position',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function tutorials(): HasMany
    {
        return $this->hasMany(Tutorial::class, 'category_id')->orderBy('position');
    }

    public function publishedTutorials(): HasMany
    {
        return $this->tutorials()->where('published', true);
    }
}
