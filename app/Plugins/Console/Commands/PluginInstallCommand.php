<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginInstallCommand extends Command
{
    protected $signature = 'plugin:install {slug}';

    protected $description = 'Run a plugin installation and enable it';

    public function handle(PluginManager $manager): int
    {
        $slug = (string) $this->argument('slug');

        if (! $manager->enable($slug)) {
            $this->error("Unable to install plugin [{$slug}].");

            return self::FAILURE;
        }

        $this->info("Plugin [{$slug}] installed and enabled.");

        return self::SUCCESS;
    }
}
