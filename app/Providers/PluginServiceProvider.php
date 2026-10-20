<?php

declare(strict_types=1);

namespace App\Providers;

use App\Plugins\Console\Commands\MakePluginCommand;
use App\Plugins\Console\Commands\PluginCacheCommand;
use App\Plugins\Console\Commands\PluginClearCommand;
use App\Plugins\Console\Commands\PluginDisableCommand;
use App\Plugins\Console\Commands\PluginEnableCommand;
use App\Plugins\Console\Commands\PluginInstallCommand;
use App\Plugins\Console\Commands\PluginListCommand;
use App\Plugins\Console\Commands\PluginUninstallCommand;
use App\Plugins\Console\Commands\PluginValidateCommand;
use App\Plugins\PluginManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class);
    }

    public function boot(): void
    {
        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakePluginCommand::class,
                PluginCacheCommand::class,
                PluginClearCommand::class,
                PluginDisableCommand::class,
                PluginEnableCommand::class,
                PluginInstallCommand::class,
                PluginListCommand::class,
                PluginUninstallCommand::class,
                PluginValidateCommand::class,
            ]);
        }

        if (! $this->shouldSkipInitialization()) {
            $this->app->make(PluginManager::class)->initialize();
        }
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive(
            'hook',
            fn (string $expression): string => "<?php echo implode('', app(\\App\\Plugins\\PluginManager::class)->renderHook({$expression})); ?>",
        );

        Blade::directive(
            'filter',
            fn (string $expression): string => "<?php echo app(\\App\\Plugins\\PluginManager::class)->filter({$expression}); ?>",
        );
    }

    private function shouldSkipInitialization(): bool
    {
        if (! $this->app->runningInConsole()) {
            return false;
        }

        $ignored = [
            'migrate',
            'db:',
            'schema:',
            'plugin:cache',
            'plugin:clear',
        ];

        $command = (string) ($_SERVER['argv'][1] ?? '');

        foreach ($ignored as $prefix) {
            if (str_starts_with($command, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
