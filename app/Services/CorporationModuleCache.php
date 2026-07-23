<?php

namespace App\Services;

use App\Models\Tenant\Corporation;
use Illuminate\Support\Facades\Cache;

class CorporationModuleCache
{
    private const KEY_PREFIX = 'corporation_modules:';

    private const TTL = 3600;

    public static function key(Corporation $corporation): string
    {
        return self::KEY_PREFIX.$corporation->id;
    }

    /**
     * @return list<string>
     */
    public static function remember(Corporation $corporation): array
    {
        return Cache::remember(
            self::key($corporation),
            self::TTL,
            fn (): array => $corporation->activeModules()->pluck('module_code')->all(),
        );
    }

    public static function forget(Corporation $corporation): void
    {
        Cache::forget(self::key($corporation));
    }
}
