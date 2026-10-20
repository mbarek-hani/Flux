<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Plugin\PluginDiscovery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PluginClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime le cache de découverte des plugins et réinitialise le cache des plugins actifs.';

    /**
     * Execute the console command.
     */
    public function handle(PluginDiscovery $discovery): int
    {
        $discovery->viderCache();
        Cache::forget('plugins.actifs');

        $this->components->info('Cache des plugins vidé avec succès !');

        return self::SUCCESS;
    }
}
