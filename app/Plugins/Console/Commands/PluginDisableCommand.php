<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginDisableCommand extends Command
{
    protected $signature = 'plugin:disable {slug}';

    protected $description = 'Disable a plugin';

    public function handle(PluginManager $manager): int
    {
        $slug = (string) $this->argument('slug');

        if (! $manager->disable($slug)) {
            $this->error("Unable to disable plugin [{$slug}].");

            return self::FAILURE;
        }

        $this->info("Plugin [{$slug}] disabled.");

        return self::SUCCESS;
    }
}
