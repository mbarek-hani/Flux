<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Plugins\Contracts\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class PluginAssetPublisher
{
    public function publish(Plugin $plugin): void
    {
        $source = $this->pluginPath($plugin).'/resources/assets';
        $destination = public_path('plugin-assets/'.$plugin->slug());

        if (! is_dir($source)) {
            return;
        }

        File::ensureDirectoryExists($destination);
        File::copyDirectory($source, $destination);
    }

    public function remove(Plugin $plugin): void
    {
        $destination = public_path('plugin-assets/'.$plugin->slug());

        if (is_dir($destination)) {
            File::deleteDirectory($destination);
        }
    }

    private function pluginPath(Plugin $plugin): string
    {
        $reflection = new \ReflectionClass($plugin);
        $file = $reflection->getFileName();

        if ($file === false) {
            Log::warning("Unable to resolve plugin path for [{$plugin->slug()}]");

            return '';
        }

        return dirname($file, 2);
    }
}
