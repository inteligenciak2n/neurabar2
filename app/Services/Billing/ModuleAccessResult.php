<?php

namespace App\Services\Billing;

use App\Enums\ModuleAccessReason;
use App\Enums\ModuleCode;

/**
 * Resultado de uma verificação de acesso a módulo. Carrega o motivo da negação
 * para que o chamador decida entre 403, paywall de upsell ou banner de billing.
 */
class ModuleAccessResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly ModuleAccessReason $reason,
        public readonly ?ModuleCode $module = null,
        public readonly ?ModuleCode $missingDependency = null,
    ) {}

    public static function granted(ModuleCode $module): self
    {
        return new self(true, ModuleAccessReason::Granted, $module);
    }

    public static function denied(ModuleAccessReason $reason, ?ModuleCode $module = null, ?ModuleCode $missingDependency = null): self
    {
        return new self(false, $reason, $module, $missingDependency);
    }

    public function message(): string
    {
        if ($this->reason === ModuleAccessReason::MissingDependency && $this->missingDependency) {
            return "Dependência não atendida: {$this->missingDependency->label()}.";
        }

        return $this->reason->label();
    }

    /**
     * @return array{reason: string, module: array{value: string, label: string}|null, missing_dependency: array{value: string, label: string}|null, message: string}
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason->value,
            'module' => $this->module?->toArray(),
            'missing_dependency' => $this->missingDependency?->toArray(),
            'message' => $this->message(),
        ];
    }
}
