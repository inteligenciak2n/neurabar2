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


    static public function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }


    static public function operationalProfiles(): array
    {
        return [
            self::Client->value,
        ];
    }


    static public function plataformProfiles(): array
    {
        return array_diff(self::values(), self::operationalProfiles());
    }

}
