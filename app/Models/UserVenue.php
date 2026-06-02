<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserVenue extends Pivot
{
    protected $connection = 'saas';

    public $incrementing = false;

    protected $table = 'user_venue';

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
