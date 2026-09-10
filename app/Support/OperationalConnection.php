<?php

namespace App\Support;

class OperationalConnection
{
    /**
     * Resolve the operational connection name for the current request/job.
     *
     * Mirrors HasOperationalConnection (used by operational models): the
     * SetVenueContext middleware binds 'operational_connection' per request;
     * outside that context (console, queue, guest routes) it falls back to
     * the shared pool connection.
     */
    public static function current(): string
    {
        return app()->bound('operational_connection')
            ? app('operational_connection')
            : 'operation_default_1';
    }
}
