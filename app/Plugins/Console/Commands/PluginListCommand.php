<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginListCommand extends Command
{
    protected $signature = 'plugin:list';

    protected $description = 'List discovered plugins and their status';

    public function handle(PluginManager $manager): int
    {
        $states = $manager->stateRepository()->all()->keyBy('slug');
        $rows = [];

        foreach ($manager->discovered() as $slug => $plugin) {
            $state = $states->get($slug);

            $rows[] = [
                $slug,
                $plugin->name(),
                $plugin->version(),
                $state?->status ?? 'discovered',
                $manager->isActive($slug) ? 'yes' : 'no',
            ];
        }

        $this->table(['Slug', 'Name', 'Version', 'Status', 'Active'], $rows);

        return self::SUCCESS;
    }
}
