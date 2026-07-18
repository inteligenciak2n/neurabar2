<?php

namespace App\Services;

use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\Cache;

class VenueModuleCache
{
    private const KEY_PREFIX = 'venue_modules:';

    private const TTL = 3600;

    public static function key(Venue $venue): string
    {
        return self::KEY_PREFIX.$venue->id;
    }

    public static function remember(Venue $venue, callable $callback): array
    {
        return Cache::remember(self::key($venue), self::TTL, $callback);
    }

    public static function forget(Venue $venue): void
    {
        Cache::forget(self::key($venue));
    }
}
