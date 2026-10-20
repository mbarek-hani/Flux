<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Plugin\PluginDiscovery;
use Illuminate\Console\Command;

class PluginCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile et met en cache la découverte des plugins pour des performances optimales.';

    /**
     * Execute the console command.
     */
    public function handle(PluginDiscovery $discovery): int
    {
        $this->components->info('Mise en cache de la découverte des plugins...');

        $plugins = $discovery->mettreEnCache();

        $count = count($plugins);
        $this->components->info("{$count} plugin(s) mis en cache avec succès !");

        return self::SUCCESS;
    }
}
