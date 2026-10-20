<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\PluginDiscovery;
use App\Plugins\PluginStateRepository;
use Illuminate\Console\Command;

class PluginClearCommand extends Command
{
    protected $signature = 'plugin:clear';

    protected $description = 'Clear the plugin discovery and state caches';

    public function handle(PluginDiscovery $discovery, PluginStateRepository $state): int
    {
        $discovery->clearCache();
        $state->forgetCache();

        $this->info('Plugin caches cleared.');

        return self::SUCCESS;
    }
}
