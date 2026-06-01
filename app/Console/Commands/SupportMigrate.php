<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SupportMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:migrate
                            {--fresh : Drop all support tables and re-run migrations}
                            {--seed : Run the support database seeders after migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run database migrations for the support connection (database/migrations/support)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Dropping all support tables...');

            Artisan::call('db:wipe', [
                '--database' => 'support',
                '--force' => true,
            ], $this->output);
        }

        $this->info('Running support migrations...');

        Artisan::call('migrate', [
            '--path' => 'database/migrations/support',
            '--database' => 'support',
            '--force' => true,
        ], $this->output);

        if ($this->option('seed')) {
            $this->info('Running support seeders...');

            Artisan::call('db:seed', [
                '--class' => 'SupportDatabaseSeeder',
                '--force' => true,
            ], $this->output);
        }

        $this->info('Support migrations completed.');

        return Command::SUCCESS;
    }
}
