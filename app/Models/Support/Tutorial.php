<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Tutorial extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'support';

    protected $table = 'support_tutorials';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'body',
        'featured_image',
        'published',
        'published_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'published_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TutorialCategory::class, 'category_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('published', true)->whereNotNull('published_at');
    }

    public static function generateSlug(string $title): string
    {
        $slug = Str::slug($title);
        $count = static::on('support')->where('slug', 'like', "{$slug}%")->count();

        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }
}
