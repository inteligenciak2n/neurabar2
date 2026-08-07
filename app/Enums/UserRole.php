<?php

namespace App\Enums;

/**
 * Operational role a user holds inside a venue (stored in `user_venue.role`).
 *
 * Platform (internal NeuraBar team) access is modelled exclusively by
 * {@see ProfileEnum}. The two enums must never share a value: when they did,
 * a platform string could be assigned as a venue role and vice-versa.
 */
enum UserRole: string
{
    case Owner = 'owner';
    case GeneralManager = 'general_manager';
    case SectionManager = 'section_manager';
    case Attendant = 'attendant';

    /** @return list<self> */
    public static function operationalRoles(): array
    {
        return self::cases();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function isOwnerOrAbove(): bool
    {
        return $this === self::Owner;
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::GeneralManager => 'General Manager',
            self::SectionManager => 'Section Manager',
            self::Attendant => 'Attendant',
        };
    }
}
