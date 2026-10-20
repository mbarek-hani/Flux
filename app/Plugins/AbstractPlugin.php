<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Plugins\Contracts\AddsTrackerScript;
use App\Plugins\Contracts\FiltersData;
use App\Plugins\Contracts\FiltersTracking;
use App\Plugins\Contracts\Plugin;
use App\Plugins\Contracts\ProvidesAssets;
use App\Plugins\Contracts\ProvidesNavigation;
use App\Plugins\Contracts\ProvidesSettings;
use App\Plugins\Contracts\ProvidesTabs;
use App\Plugins\Contracts\RegistersHooks;
use App\Plugins\Contracts\RegistersListeners;
use App\Plugins\Data\HookDefinition;
use App\Plugins\Data\PluginManifest;
use App\Plugins\Models\Plugin as PluginModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

abstract class AbstractPlugin implements AddsTrackerScript, FiltersData, FiltersTracking, Plugin, ProvidesAssets, ProvidesNavigation, ProvidesSettings, ProvidesTabs, RegistersHooks, RegistersListeners
{
    private ?PluginManifest $manifestCache = null;

    /** @var array<string, mixed>|null */
    private ?array $configurationCache = null;

    abstract public function slug(): string;

    abstract public function name(): string;

    abstract public function version(): string;

    public function manifest(): PluginManifest
    {
        if ($this->manifestCache !== null) {
            return $this->manifestCache;
        }

        $manifest = app(ManifestReader::class)->readFromClass(static::class);

        if ($manifest !== null) {
            return $this->manifestCache = $manifest;
        }

        $namespace = explode('\\', static::class);
        $namespace = count($namespace) >= 2 ? $namespace[0].'\\'.$namespace[1] : '';

        return $this->manifestCache = new PluginManifest(
            slug: $this->slug(),
            name: $this->name(),
            version: $this->version(),
            description: '',
            author: 'Unknown',
            license: 'Unspecified',
            url: null,
            class: static::class,
            namespace: $namespace,
            hooks: [],
            tabs: [],
            navigation: [],
            settings: [],
            routes: [],
            raw: [],
        );
    }

    public function register(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerListeners();
    }

    public function enable(): void
    {
        app(PluginAssetPublisher::class)->publish($this);

        Log::info("Plugin [{$this->slug()}] enabled.");
    }

    public function disable(): void
    {
        app(PluginAssetPublisher::class)->remove($this);

        Log::info("Plugin [{$this->slug()}] disabled.");
    }

    public function install(): void
    {
        app(PluginMigrationManager::class)->run($this);

        Log::info("Plugin [{$this->slug()}] installed.");
    }

    public function uninstall(): void
    {
        app(PluginMigrationManager::class)->rollback($this);
        app(PluginAssetPublisher::class)->remove($this);

        Log::info("Plugin [{$this->slug()}] uninstalled.");
    }

    /**
     * @return list<HookDefinition>
     */
    public function hooks(): array
    {
        return $this->manifest()->hooks;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function renderHook(string $hook, array $data = []): string
    {
        return '';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function filterData(string $name, mixed $value, array $context = []): mixed
    {
        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function filterVisit(array $data, Request $request): ?array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function filterEvent(array $data, Request $request): ?array
    {
        return $data;
    }

    /**
     * @return list<array{id: string, label: string, icon: string}>
     */
    public function tabs(): array
    {
        return $this->manifest()->tabs;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function navigation(): array
    {
        return $this->manifest()->navigation;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function settings(): array
    {
        return $this->manifest()->settings;
    }

    /**
     * @return list<string>
     */
    public function stylesheets(): array
    {
        return $this->publishedAssets('css');
    }

    /**
     * @return list<string>
     */
    public function scripts(): array
    {
        return $this->publishedAssets('js');
    }

    public function trackerScript(): ?string
    {
        return null;
    }

    /**
     * @return array<class-string, list<class-string|callable>|class-string|callable>
     */
    public function listeners(): array
    {
        return [];
    }

    public function path(string $relative = ''): string
    {
        $reflection = new \ReflectionClass(static::class);
        $file = $reflection->getFileName();
        $root = $file !== false ? dirname($file, 2) : '';

        return $root.($relative !== '' ? '/'.ltrim($relative, '/') : '');
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        if ($this->configurationCache === null) {
            $plugin = PluginModel::query()->where('slug', $this->slug())->first();
            $this->configurationCache = $plugin?->configuration ?? [];
        }

        return $this->configurationCache[$key] ?? $default;
    }

    public function refreshConfiguration(): void
    {
        $this->configurationCache = null;
    }

    private function registerRoutes(): void
    {
        $configuration = $this->manifest()->routeConfiguration();
        $slug = $this->slug();

        if (($configuration['web'] ?? null) !== false && is_file($this->path('routes/web.php'))) {
            $web = is_array($configuration['web'] ?? null) ? $configuration['web'] : [];
            $middleware = $web['middleware'] ?? ['web', 'auth'];
            $prefix = $web['prefix'] ?? 'plugins/'.$slug;
            $name = $web['name'] ?? 'plugins.'.$slug.'.';

            Route::middleware($middleware)
                ->prefix($prefix)
                ->name($name)
                ->group($this->path('routes/web.php'));
        }

        if (($configuration['api'] ?? null) !== false && is_file($this->path('routes/api.php'))) {
            $api = is_array($configuration['api'] ?? null) ? $configuration['api'] : [];
            $middleware = $api['middleware'] ?? ['api'];
            $prefix = $api['prefix'] ?? 'api/plugins/'.$slug;
            $name = $api['name'] ?? 'api.plugins.'.$slug.'.';

            Route::middleware($middleware)
                ->prefix($prefix)
                ->name($name)
                ->group($this->path('routes/api.php'));
        }
    }

    private function registerViews(): void
    {
        $views = $this->path('resources/views');

        if (is_dir($views)) {
            app('view')->addNamespace($this->slug(), $views);
        }
    }

    private function registerListeners(): void
    {
        foreach ($this->listeners() as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function publishedAssets(string $extension): array
    {
        $assets = [];

        foreach (['style', 'plugin'] as $name) {
            $relative = 'plugin-assets/'.$this->slug().'/'.$name.'.'.$extension;

            if (is_file(public_path($relative))) {
                $assets[] = $relative;
            }
        }

        return array_values(array_unique($assets));
    }
}
