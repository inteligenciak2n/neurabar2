<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class assetsLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from "public/assets" to "storage/app/public"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $resourcePath = base_path('resources/assets');
        $linkPath = storage_path('app/public/assets');

        if (! is_dir($resourcePath)) {
            $this->error('The "resources/assets" directory does not exist. Make sure it is present and tracked by git.');
            return 1;
        }

        if (file_exists($linkPath) || is_link($linkPath)) {
            $this->warn('The symlink "storage/app/public/assets" already exists. Skipping.');
            return 0;
        }

        symlink($resourcePath, $linkPath);
        $this->info('Symbolic link created: storage/app/public/assets → resources/assets');

        return 0;
    }
}
