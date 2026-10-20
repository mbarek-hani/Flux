<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Plugins\Contracts\Plugin;
use App\Plugins\Models\PluginMigration;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PluginMigrationManager
{
    public function __construct(private readonly Migrator $migrator) {}

    public function run(Plugin $plugin): void
    {
        $path = $this->migrationPath($plugin);

        if (! is_dir($path)) {
            return;
        }

        $known = PluginMigration::query()
            ->where('plugin_slug', $plugin->slug())
            ->pluck('migration')
            ->all();

        $files = $this->migrator->getMigrationFiles($path);
        $this->migrator->run([$path]);

        $ran = $this->migrator->getRepository()->getRan();

        foreach (array_keys($files) as $migration) {
            if (! in_array($migration, $ran, true) || in_array($migration, $known, true)) {
                continue;
            }

            $batch = DB::table('migrations')->where('migration', $migration)->value('batch');

            PluginMigration::query()->create([
                'plugin_slug' => $plugin->slug(),
                'migration' => $migration,
                'batch' => (int) $batch,
                'ran_at' => now(),
            ]);
        }

        Log::info("Migrations completed for plugin [{$plugin->slug()}].");
    }

    public function rollback(Plugin $plugin): void
    {
        $path = $this->migrationPath($plugin);

        if (! is_dir($path)) {
            return;
        }

        $migrations = PluginMigration::query()
            ->where('plugin_slug', $plugin->slug())
            ->orderByDesc('batch')
            ->orderByDesc('id')
            ->get();

        if ($migrations->isEmpty()) {
            return;
        }

        $this->migrator->rollback([$path], [
            'pretend' => false,
            'step' => $migrations->count(),
        ]);

        PluginMigration::query()->where('plugin_slug', $plugin->slug())->delete();

        Log::info("Migrations rolled back for plugin [{$plugin->slug()}].");
    }

    private function migrationPath(Plugin $plugin): string
    {
        $reflection = new \ReflectionClass($plugin);
        $file = $reflection->getFileName();

        if ($file === false) {
            return '';
        }

        return dirname($file, 2).'/database/migrations';
    }
}
