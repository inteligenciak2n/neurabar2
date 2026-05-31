<?php

namespace App\Enums;

enum ProfileEnum: string
{
    // Platform (internal NeuraBar team)
    case SuperAdmin = 'super_admin';
    case Finance = 'finance';
    case Registration = 'registration';
    case ReadOnly = 'read_only';

    // Operational (SaaS clients)
    case Client = 'client';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function operationalProfiles(): array
    {
        return [
            self::Client->value,
        ];
    }

    public static function platformProfiles(): array
    {
        return array_diff(self::values(), self::operationalProfiles());
    }
}
