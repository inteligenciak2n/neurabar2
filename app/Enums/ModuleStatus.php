<?php

namespace App\Enums;

enum ModuleStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Canceled = 'canceled';

        public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
            self::Suspended => 'Suspenso',
            self::Canceled => 'Cancelado',
        };
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function all(): array
    {
        return array_map(fn (self $module) => [
            'value' => $module->value,
            'label' => $module->label(),
        ], self::cases());
    }
}
