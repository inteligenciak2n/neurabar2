<?php

namespace App\Enums;

enum ModuleAccessReason: string
{
    case Granted = 'granted';
    case NoVenue = 'no_venue';
    case UnknownModule = 'unknown_module';
    case BillingBlocked = 'billing_blocked';
    case NotContracted = 'not_contracted';
    case NotActiveForVenue = 'not_active_for_venue';
    case MissingDependency = 'missing_dependency';

    public function label(): string
    {
        return match ($this) {
            self::Granted => 'Acesso liberado.',
            self::NoVenue => 'Nenhuma unidade selecionada.',
            self::UnknownModule => 'Módulo não disponível.',
            self::BillingBlocked => 'Acesso suspenso por questões de faturamento.',
            self::NotContracted => 'Este módulo não está contratado para esta conta.',
            self::NotActiveForVenue => 'Este módulo não está ativo para esta unidade.',
            self::MissingDependency => 'Dependência não atendida.',
        };
    }

    /**
     * Motivos que representam uma oportunidade de venda: o cliente está em dia,
     * mas ainda não contratou/ativou o módulo. O front usa isso para escolher
     * entre paywall de upsell e erro de acesso.
     */
    public function isUpsellOpportunity(): bool
    {
        return match ($this) {
            self::NotContracted, self::NotActiveForVenue, self::MissingDependency => true,
            default => false,
        };
    }
}
