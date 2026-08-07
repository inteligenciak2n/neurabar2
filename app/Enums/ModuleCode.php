<?php

namespace App\Enums;

enum ModuleCode: string
{
    case Menu = 'menu';
    case Kds = 'kds';
    case Taker = 'taker';
    case DirectWaiter = 'direct_waiter';
    case Delivery = 'delivery';
    case ProductionDashboard = 'production_dashboard';
    case FinancialDashboard = 'financial_dashboard';
    case DirectPrint = 'direct_print';
    case FiscalNote = 'fiscal_note';
    case VoiceCommand = 'voice_command';

    public function dependsOn(): array
    {
        return match ($this) {
            self::Menu => [],
            self::Kds, self::Taker, self::Delivery,
            self::ProductionDashboard, self::FinancialDashboard,
            self::DirectPrint, self::FiscalNote, self::VoiceCommand => [self::Menu],
            self::DirectWaiter => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Menu => 'Cardápio',
            self::Kds => 'KDS',
            self::Taker => 'Anotar Pedido',
            self::DirectWaiter => 'Direct Garçom',
            self::Delivery => 'Delivery',
            self::ProductionDashboard => 'Dashboard de Produção',
            self::FinancialDashboard => 'Dashboard Financeiro',
            self::DirectPrint => 'Impressão Direta',
            self::FiscalNote => 'Nota Fiscal',
            self::VoiceCommand => 'Comando por Voz',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function all(): array
    {
        return array_map(fn (self $module) => $module->toArray(), self::cases());
    }
}
