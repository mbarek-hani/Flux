<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginEnableCommand extends Command
{
    protected $signature = 'plugin:enable {slug}';

    protected $description = 'Enable a plugin';

    public function handle(PluginManager $manager): int
    {
        $slug = (string) $this->argument('slug');

        if (! $manager->enable($slug)) {
            $this->error("Unable to enable plugin [{$slug}].");

            return self::FAILURE;
        }

        $this->info("Plugin [{$slug}] enabled.");

        return self::SUCCESS;
    }
}
