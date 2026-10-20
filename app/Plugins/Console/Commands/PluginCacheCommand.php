<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\PluginDiscovery;
use Illuminate\Console\Command;

class PluginCacheCommand extends Command
{
    protected $signature = 'plugin:cache';

    protected $description = 'Build the plugin discovery cache';

    public function handle(PluginDiscovery $discovery): int
    {
        $plugins = $discovery->cache();

        $this->info(count($plugins).' plugin(s) cached.');

        return self::SUCCESS;
    }
}
