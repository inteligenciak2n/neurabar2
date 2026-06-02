<?php

namespace App\Models\Concerns;

/**
 * Trait para todos os models do banco operacional.
 *
 * Retorna a conexão operacional resolvida pelo SetVenueContext middleware
 * e registrada no container como 'operational_connection'.
 * Fallback: 'operation_default_1' para contextos sem venue (console, queue).
 */
trait HasOperationalConnection
{
    public function getConnectionName(): string
    {
        return app()->bound('operational_connection')
            ? app('operational_connection')
            : 'operation_default_1';
    }
}
