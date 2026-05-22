<?php

namespace App\Concerns;

use App\Models\Tenant\Venue;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToVenue
{
    /**
     * Boot the trait and register the global TenantScope.
     */
    public static function bootBelongsToVenue(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model): void {
            if (empty($model->venue_id) && app()->bound('tenant') && app('tenant') !== null) {
                $model->venue_id = app('tenant')->id;
            }
        });
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
