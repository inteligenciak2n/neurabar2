<?php

namespace App\Enums;

enum UserRole: string
{
    // Platform (internal NeuraBar team)
    case SuperAdmin = 'super_admin';
    case Finance = 'finance';
    case Registration = 'registration';
    case ReadOnly = 'read_only';

    // Operational (SaaS clients)
    case CorporationAdmin = 'corporation_admin';
    case Owner = 'owner';
    case GeneralManager = 'general_manager';
    case SectionManager = 'section_manager';
    case Attendant = 'attendant';

    /** @return list<self> */
    public static function platformRoles(): array
    {
        return [self::SuperAdmin, self::Finance, self::Registration, self::ReadOnly];
    }

    /** @return list<self> */
    public static function operationalRoles(): array
    {
        return [
            self::CorporationAdmin,
            self::Owner,
            self::GeneralManager,
            self::SectionManager,
            self::Attendant,
        ];
    }

    public function isOwnerOrAbove(): bool
    {
        return in_array($this, [self::Owner, self::CorporationAdmin], true);
    }

    public function isPlatform(): bool
    {
        return in_array($this, self::platformRoles(), true);
    }

    public function isOperational(): bool
    {
        return in_array($this, self::operationalRoles(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Finance => 'Finance',
            self::Registration => 'Registration',
            self::ReadOnly => 'Read Only',
            self::CorporationAdmin => 'Corporation Admin',
            self::Owner => 'Owner',
            self::GeneralManager => 'General Manager',
            self::SectionManager => 'Section Manager',
            self::Attendant => 'Attendant',
        };
    }
}
