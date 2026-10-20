<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Plugins\Data\PluginManifest;
use Illuminate\Support\Facades\Log;

final class ManifestReader
{
    public function read(string $pluginPath): ?PluginManifest
    {
        $manifestPath = rtrim($pluginPath, '/').'/manifest.json';

        if (! is_file($manifestPath)) {
            return null;
        }

        $contents = file_get_contents($manifestPath);

        if ($contents === false) {
            Log::error("Unable to read plugin manifest: {$manifestPath}");

            return null;
        }

        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            Log::error("Invalid plugin manifest JSON in {$manifestPath}: ".json_last_error_msg());

            return null;
        }

        $manifest = PluginManifest::fromArray($data);

        if ($manifest === null) {
            Log::error("Invalid plugin manifest structure in {$manifestPath}");

            return null;
        }

        return $manifest;
    }

    public function readFromClass(string $class): ?PluginManifest
    {
        $reflection = new \ReflectionClass($class);
        $file = $reflection->getFileName();

        if ($file === false) {
            return null;
        }

        return $this->read(dirname($file, 2));
    }
}
