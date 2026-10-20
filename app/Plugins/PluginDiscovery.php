<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Plugins\Contracts\Plugin;
use App\Plugins\Data\DiscoveredPlugin;
use Illuminate\Support\Facades\Log;

final class PluginDiscovery
{
    public function __construct(
        private readonly PluginAutoloader $autoloader,
        private readonly ManifestReader $reader,
        private readonly ?string $pluginsPath = null,
        private readonly ?string $cachePath = null,
    ) {}

    /**
     * @return array<string, DiscoveredPlugin>
     */
    public function discover(): array
    {
        return $this->isCached() ? $this->loadFromCache() : $this->scan();
    }

    /**
     * @return array<string, DiscoveredPlugin>
     */
    public function scan(): array
    {
        $plugins = [];
        $path = $this->pluginsPath();

        if (! is_dir($path)) {
            return $plugins;
        }

        $directories = glob($path.'/*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $directory) {
            $manifest = $this->reader->read($directory);

            if ($manifest === null) {
                continue;
            }

            $this->autoloader->addNamespace($manifest->namespace, $directory.'/src');

            $plugins[$manifest->slug] = new DiscoveredPlugin($manifest, $directory);
        }

        return $plugins;
    }

    public function instantiate(DiscoveredPlugin $plugin): ?Plugin
    {
        $this->autoloader->loadVendor($plugin->path);
        $this->autoloader->addNamespace($plugin->namespace(), $plugin->path.'/src');

        if (! class_exists($plugin->class())) {
            Log::error("Plugin class not found: {$plugin->class()}");

            return null;
        }

        if (! is_subclass_of($plugin->class(), Plugin::class)) {
            Log::error("Plugin class {$plugin->class()} does not implement ".Plugin::class);

            return null;
        }

        try {
            $instance = app($plugin->class());

            return $instance instanceof Plugin ? $instance : null;
        } catch (\Throwable $exception) {
            Log::error("Failed to instantiate plugin [{$plugin->slug()}]: {$exception->getMessage()}", [
                'class' => $plugin->class(),
                'exception' => $exception,
            ]);

            return null;
        }
    }

    /**
     * @return array<string, DiscoveredPlugin>
     */
    public function cache(): array
    {
        $plugins = $this->scan();
        $data = [];

        foreach ($plugins as $slug => $plugin) {
            $data[$slug] = $plugin->toArray();
        }

        $directory = dirname($this->cachePath());

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $this->cachePath(),
            '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($data, true).';'.PHP_EOL,
        );

        return $plugins;
    }

    public function clearCache(): bool
    {
        if (! file_exists($this->cachePath())) {
            return true;
        }

        return unlink($this->cachePath());
    }

    public function isCached(): bool
    {
        return file_exists($this->cachePath());
    }

    public function cachePath(): string
    {
        return $this->cachePath ?? base_path('bootstrap/cache/plugins.php');
    }

    public function pluginsPath(): string
    {
        return $this->pluginsPath ?? base_path('plugins');
    }

    /**
     * @return array<string, DiscoveredPlugin>
     */
    private function loadFromCache(): array
    {
        try {
            $data = require $this->cachePath();

            if (! is_array($data)) {
                return $this->scan();
            }

            $plugins = [];

            foreach ($data as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $plugin = DiscoveredPlugin::fromArray($entry);

                if ($plugin === null) {
                    continue;
                }

                $this->autoloader->addNamespace($plugin->namespace(), $plugin->path.'/src');
                $plugins[$plugin->slug()] = $plugin;
            }

            return $plugins;
        } catch (\Throwable $exception) {
            Log::error('Unable to load plugin cache: '.$exception->getMessage());

            return $this->scan();
        }
    }
}
