<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Events\PluginDisabled;
use App\Events\PluginEnabled;
use App\Plugins\Contracts\FiltersTracking;
use App\Plugins\Contracts\Plugin;
use App\Plugins\Contracts\ProvidesAssets;
use App\Plugins\Contracts\ProvidesNavigation;
use App\Plugins\Contracts\ProvidesSettings;
use App\Plugins\Contracts\ProvidesTabs;
use App\Plugins\Contracts\RegistersHooks;
use App\Plugins\Data\DiscoveredPlugin;
use App\Plugins\Data\PluginManifest;
use App\Plugins\Models\Plugin as PluginModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class PluginManager
{
    /** @var array<string, DiscoveredPlugin> */
    private array $discovered = [];

    /** @var array<string, Plugin> */
    private array $active = [];

    public function __construct(
        private readonly PluginDiscovery $discovery,
        private readonly HookRegistry $hooks,
        private readonly PluginStateRepository $state,
    ) {}

    public function initialize(): void
    {
        $this->discovered = $this->discovery->discover();

        if (! $this->state->tableExists()) {
            return;
        }

        foreach ($this->state->activeSlugs() as $slug) {
            $discovered = $this->discovered[$slug] ?? null;

            if ($discovered === null) {
                Log::warning("Active plugin [{$slug}] was not discovered.");

                continue;
            }

            $plugin = $this->discovery->instantiate($discovered);

            if ($plugin === null) {
                $this->state->markFailed($slug, 'Plugin could not be instantiated.', $discovered->manifest);

                continue;
            }

            $this->register($plugin);
        }
    }

    public function reset(): void
    {
        $this->discovery->clearCache();
        $this->state->forgetCache();
        $this->hooks->clear();
        $this->active = [];
        $this->discovered = [];

        $this->initialize();
    }

    public function install(string $slug): bool
    {
        $discovered = $this->discovered[$slug] ?? null;

        if ($discovered === null) {
            return false;
        }

        $plugin = $this->discovery->instantiate($discovered);

        if ($plugin === null) {
            $this->state->markFailed($slug, 'Plugin could not be instantiated.', $discovered->manifest);

            return false;
        }

        try {
            $plugin->install();
            $this->state->markInstalled($slug, $discovered->manifest);

            return true;
        } catch (\Throwable $exception) {
            $this->fail($slug, $exception, $discovered->manifest);

            return false;
        }
    }

    public function enable(string $slug): bool
    {
        if (isset($this->active[$slug])) {
            return true;
        }

        $discovered = $this->discovered[$slug] ?? null;

        if ($discovered === null) {
            return false;
        }

        $plugin = $this->discovery->instantiate($discovered);

        if ($plugin === null) {
            $this->state->markFailed($slug, 'Plugin could not be instantiated.', $discovered->manifest);

            return false;
        }

        try {
            $state = $this->state->find($slug);

            if ($state === null || $state->installed_at === null) {
                $plugin->install();
                $this->state->markInstalled($slug, $discovered->manifest);
            }

            $plugin->enable();
            $this->state->enable($slug, $discovered->manifest);
            $this->register($plugin);

            PluginEnabled::dispatch($plugin, $slug);

            return true;
        } catch (\Throwable $exception) {
            $this->fail($slug, $exception, $discovered->manifest);

            return false;
        }
    }

    public function disable(string $slug): bool
    {
        $plugin = $this->active[$slug] ?? $this->instantiate($slug);

        try {
            $plugin?->disable();

            $this->state->disable($slug);
            $this->hooks->unregister($slug);
            unset($this->active[$slug]);

            PluginDisabled::dispatch($slug);

            return true;
        } catch (\Throwable $exception) {
            $this->fail($slug, $exception);

            return false;
        }
    }

    public function uninstall(string $slug): bool
    {
        $plugin = $this->active[$slug] ?? $this->instantiate($slug);

        try {
            $plugin?->uninstall();

            $this->state->disable($slug);
            $this->hooks->unregister($slug);
            unset($this->active[$slug], $this->discovered[$slug]);
            $this->state->delete($slug);
            $this->discovery->clearCache();

            return true;
        } catch (\Throwable $exception) {
            $this->fail($slug, $exception);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function renderHook(string $hook, array $data = []): array
    {
        return $this->hooks->render($hook, $data);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function filter(string $name, mixed $value, array $context = []): mixed
    {
        return $this->hooks->filter($name, $value, $context);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function filterVisit(array $data, Request $request): ?array
    {
        foreach ($this->active as $plugin) {
            if (! $plugin instanceof FiltersTracking) {
                continue;
            }

            try {
                $data = $plugin->filterVisit($data, $request);
            } catch (\Throwable $exception) {
                Log::error("Plugin [{$plugin->slug()}] filterVisit failed: {$exception->getMessage()}");
            }

            if ($data === null) {
                return null;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function filterEvent(array $data, Request $request): ?array
    {
        foreach ($this->active as $plugin) {
            if (! $plugin instanceof FiltersTracking) {
                continue;
            }

            try {
                $data = $plugin->filterEvent($data, $request);
            } catch (\Throwable $exception) {
                Log::error("Plugin [{$plugin->slug()}] filterEvent failed: {$exception->getMessage()}");
            }

            if ($data === null) {
                return null;
            }
        }

        return $data;
    }

    /**
     * @return list<array{id: string, label: string, icon: string, plugin: string}>
     */
    public function tabs(): array
    {
        $tabs = [];

        foreach ($this->active as $slug => $plugin) {
            if (! $plugin instanceof ProvidesTabs) {
                continue;
            }

            foreach ($plugin->tabs() as $tab) {
                $tab['plugin'] = $slug;
                $tabs[] = $tab;
            }
        }

        return $tabs;
    }

    /**
     * @return array<string, array{name: string, fields: list<array<string, mixed>>}>
     */
    public function settings(): array
    {
        $settings = [];

        foreach ($this->active as $slug => $plugin) {
            if (! $plugin instanceof ProvidesSettings) {
                continue;
            }

            $fields = $plugin->settings();

            if ($fields === []) {
                continue;
            }

            $settings[$slug] = [
                'name' => $plugin->name(),
                'fields' => $fields,
            ];
        }

        return $settings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function navigation(): array
    {
        $items = [];

        foreach ($this->active as $slug => $plugin) {
            if (! $plugin instanceof ProvidesNavigation) {
                continue;
            }

            foreach ($plugin->navigation() as $item) {
                $item['plugin'] = $slug;
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function stylesheets(): array
    {
        $stylesheets = [];

        foreach ($this->active as $plugin) {
            if ($plugin instanceof ProvidesAssets) {
                $stylesheets = [...$stylesheets, ...$plugin->stylesheets()];
            }
        }

        return array_values(array_unique($stylesheets));
    }

    /**
     * @return list<string>
     */
    public function scripts(): array
    {
        $scripts = [];

        foreach ($this->active as $plugin) {
            if ($plugin instanceof ProvidesAssets) {
                $scripts = [...$scripts, ...$plugin->scripts()];
            }
        }

        return array_values(array_unique($scripts));
    }

    public function trackerScript(): string
    {
        $script = '';

        foreach ($this->active as $slug => $plugin) {
            $code = $plugin->trackerScript();

            if ($code !== null && $code !== '') {
                $script .= PHP_EOL.'/* Plugin: '.$slug.' */'.PHP_EOL
                    .'(function () { try {'.PHP_EOL.$code.PHP_EOL
                    ."} catch (error) { console.error('[Flux Plugin Error: ".$slug."]', error); } })();".PHP_EOL;
            }
        }

        return $script;
    }

    public function plugin(string $slug): ?Plugin
    {
        return $this->active[$slug] ?? null;
    }

    public function instance(string $slug): ?Plugin
    {
        return $this->active[$slug] ?? $this->instantiate($slug);
    }

    public function manifest(string $slug): ?PluginManifest
    {
        return ($this->discovered[$slug] ?? null)?->manifest;
    }

    public function model(string $slug): ?PluginModel
    {
        return $this->state->find($slug);
    }

    public function isActive(string $slug): bool
    {
        return isset($this->active[$slug]);
    }

    /**
     * @return array<string, DiscoveredPlugin>
     */
    public function discovered(): array
    {
        return $this->discovered;
    }

    /**
     * @return array<string, Plugin>
     */
    public function active(): array
    {
        return $this->active;
    }

    public function discovery(): PluginDiscovery
    {
        return $this->discovery;
    }

    public function stateRepository(): PluginStateRepository
    {
        return $this->state;
    }

    private function instantiate(string $slug): ?Plugin
    {
        $discovered = $this->discovered[$slug] ?? null;

        return $discovered === null ? null : $this->discovery->instantiate($discovered);
    }

    private function register(Plugin $plugin): void
    {
        try {
            $plugin->register();

            if ($plugin instanceof RegistersHooks) {
                $this->hooks->register($plugin, $plugin->hooks());
            }

            $this->active[$plugin->slug()] = $plugin;
        } catch (\Throwable $exception) {
            $this->fail($plugin->slug(), $exception);
        }
    }

    private function fail(string $slug, \Throwable $exception, ?PluginManifest $manifest = null): void
    {
        Log::error("Plugin [{$slug}] failed: {$exception->getMessage()}", [
            'exception' => $exception,
        ]);

        if (Schema::hasTable('plugins')) {
            $this->state->markFailed($slug, $exception->getMessage(), $manifest);
        }

        unset($this->active[$slug]);
        $this->hooks->unregister($slug);
    }
}
