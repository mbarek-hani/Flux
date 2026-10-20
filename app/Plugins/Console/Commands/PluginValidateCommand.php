<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\Contracts\Plugin;
use App\Plugins\PluginDiscovery;
use Illuminate\Console\Command;

class PluginValidateCommand extends Command
{
    protected $signature = 'plugin:validate';

    protected $description = 'Validate discovered plugin manifests and classes';

    public function handle(PluginDiscovery $discovery): int
    {
        $plugins = $discovery->scan();
        $invalid = 0;

        foreach ($plugins as $slug => $plugin) {
            $instance = $discovery->instantiate($plugin);

            if (! $instance instanceof Plugin) {
                $this->error("{$slug}: invalid plugin class");
                $invalid++;

                continue;
            }

            $this->info("{$slug}: ok");
        }

        if ($plugins === []) {
            $this->warn('No plugins discovered.');
        }

        return $invalid === 0 ? self::SUCCESS : self::FAILURE;
    }
}
