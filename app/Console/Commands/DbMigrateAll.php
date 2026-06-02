<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DbMigrateAll extends Command
{
    protected $signature = 'db:migrate-all
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed the database after migrating}
                            {--force : Force the operation to run in production}';

    protected $description = 'Run migrations for all databases (saas + all operational shared pools)';

    public function handle(): int
    {
        $this->ensureDatabasesExist();
        $this->migrateSaas();
        $this->migrateOperationalShared();

        $this->newLine();
        $this->components->info('All migrations completed successfully.');

        if ($this->option('seed')) {
            $this->components->info('Seeding databases...');
            $exitCode = $this->call('db:seed', ['--force' => true]);

            if ($exitCode !== self::SUCCESS) {
                $this->components->error('Seeding failed.');
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Garante que os bancos configurados existam no PostgreSQL.
     * Util em ambientes de teste onde os bancos podem nao ter sido criados pelo init script.
     */
    private function ensureDatabasesExist(): void
    {
        $connectionsToCheck = array_merge(
            ['saas'],
            collect(config('database.connections'))
                ->keys()
                ->filter(fn (string $name): bool => str_starts_with($name, 'operation_default_'))
                ->toArray()
        );

        $host = config('database.connections.saas.host');
        $port = config('database.connections.saas.port', 5432);
        $username = config('database.connections.saas.username');
        $password = config('database.connections.saas.password');

        $pdo = new \PDO(
            "pgsql:host={$host};port={$port};dbname=postgres",
            $username,
            $password,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        foreach ($connectionsToCheck as $connection) {
            $dbName = config("database.connections.{$connection}.database");

            if (! $dbName) {
                continue;
            }

            $exists = $pdo->query(
                "SELECT 1 FROM pg_database WHERE datname = '{$dbName}'"
            )->fetchColumn();

            if (! $exists) {
                $pdo->exec("CREATE DATABASE \"{$dbName}\"");
                $this->line("  -> Banco [{$dbName}] criado.");
            }
        }
    }

    private function migrateSaas(): void
    {
        $this->components->info('Migrating SaaS central database...');

        if ($this->option('fresh')) {
            $this->runMigrateFreshMulti('saas', [
                'database/migrations/saas',
                'database/migrations/saas/support',
            ]);
        } else {
            $this->runMigrate('saas', 'database/migrations/saas');
            $this->runMigrate('saas', 'database/migrations/saas/support');
        }
    }

    /**
     * Executa migrate:fresh uma unica vez (drop all) e depois roda migrations
     * de multiplos paths na mesma conexao, sem dropar entre eles.
     */
    private function runMigrateFreshMulti(string $connection, array $paths): void
    {
        $exitCode = $this->call('migrate:fresh', [
            '--database' => $connection,
            '--path' => $paths[0],
            '--no-interaction' => true,
            '--force' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            throw new \RuntimeException("migrate:fresh failed for [{$connection}].");
        }

        foreach (array_slice($paths, 1) as $path) {
            $exitCode = $this->call('migrate', [
                '--database' => $connection,
                '--path' => $path,
                '--no-interaction' => true,
                '--force' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                throw new \RuntimeException("migrate failed for [{$connection}] at [{$path}].");
            }
        }
    }

    private function migrateOperationalShared(): void
    {
        $operationalConnections = collect(config('database.connections'))
            ->keys()
            ->filter(fn (string $name): bool => str_starts_with($name, 'operation_default_'));

        foreach ($operationalConnections as $connection) {
            $this->components->info("Migrating operational shared database: {$connection}...");
            $this->runMigrate($connection, 'database/migrations/operational');
        }
    }

    private function runMigrate(string $connection, string $path): void
    {
        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        $options = [
            '--database' => $connection,
            '--path' => $path,
            '--no-interaction' => true,
        ];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $exitCode = $this->call($command, $options);

        if ($exitCode !== self::SUCCESS) {
            $this->components->error("Migration failed for connection [{$connection}] at path [{$path}].");
            throw new \RuntimeException("Migration failed for [{$connection}].");
        }
    }
}
