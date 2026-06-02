<?php

namespace Tests;

use App\Console\Commands\DbMigrateAll;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Substituto para o RefreshDatabase do Laravel em ambientes multi-banco.
 * Usa o RefreshDatabase como base (para ser detectado pelo setUpTraits()),
 * sobrescreve migrateDatabases() para migrar todos os bancos.
 */
trait RefreshAllDatabases
{
    use RefreshDatabase {
        RefreshDatabase::migrateDatabases as laravelMigrateDatabases;
    }

    protected function migrateDatabases()
    {
        $kernel = $this->app->make(Kernel::class);
        $kernel->registerCommand(
            $this->app->make(DbMigrateAll::class)
        );

        $this->artisan('db:migrate-all', ['--fresh' => true, '--force' => true]);
    }

    protected function connectionsToTransact(): array
    {
        return ['saas', 'operation_default_1', 'operation_default_2'];
    }
}
