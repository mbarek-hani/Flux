<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginUninstallCommand extends Command
{
    protected $signature = 'plugin:uninstall {slug}';

    protected $description = 'Disable a plugin and roll back its migrations';

    public function handle(PluginManager $manager): int
    {
        $slug = (string) $this->argument('slug');

        if (! $manager->uninstall($slug)) {
            $this->error("Unable to uninstall plugin [{$slug}].");

            return self::FAILURE;
        }

        $this->info("Plugin [{$slug}] uninstalled.");

        return self::SUCCESS;
    }
}
