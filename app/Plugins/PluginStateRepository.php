<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Plugins\Data\PluginManifest;
use App\Plugins\Data\PluginStatus;
use App\Plugins\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class PluginStateRepository
{
    private const ACTIVE_CACHE_KEY = 'plugins.active_slugs';

    public function tableExists(): bool
    {
        try {
            return Schema::hasTable('plugins');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    public function activeSlugs(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, function (): array {
            return Plugin::query()->where('is_active', true)->pluck('slug')->all();
        });
    }

    public function find(string $slug): ?Plugin
    {
        return $this->tableExists() ? Plugin::query()->where('slug', $slug)->first() : null;
    }

    /**
     * @return Collection<int, Plugin>
     */
    public function all(): Collection
    {
        return $this->tableExists() ? Plugin::query()->get() : collect();
    }

    public function markInstalled(string $slug, PluginManifest $manifest): Plugin
    {
        $plugin = Plugin::query()->firstOrNew(['slug' => $slug]);
        $plugin->fill([
            'name' => $manifest->name,
            'version' => $manifest->version,
            'description' => $manifest->description,
            'author' => $manifest->author,
            'license' => $manifest->license,
            'manifest' => $manifest->toArray(),
            'status' => PluginStatus::Installed,
            'installed_at' => $plugin->installed_at ?? now(),
        ]);
        $plugin->save();

        return $plugin;
    }

    public function enable(string $slug, PluginManifest $manifest): Plugin
    {
        $plugin = Plugin::query()->firstOrNew(['slug' => $slug]);
        $plugin->fill([
            'name' => $manifest->name,
            'version' => $manifest->version,
            'description' => $manifest->description,
            'author' => $manifest->author,
            'license' => $manifest->license,
            'manifest' => $manifest->toArray(),
            'is_active' => true,
            'status' => PluginStatus::Enabled,
            'installed_at' => $plugin->installed_at ?? now(),
            'enabled_at' => now(),
            'disabled_at' => null,
            'last_error' => null,
            'last_error_at' => null,
        ]);
        $plugin->save();

        $this->forgetCache();

        return $plugin;
    }

    public function disable(string $slug): void
    {
        Plugin::query()->where('slug', $slug)->update([
            'is_active' => false,
            'status' => PluginStatus::Disabled,
            'disabled_at' => now(),
        ]);

        $this->forgetCache();
    }

    public function markFailed(string $slug, string $error, ?PluginManifest $manifest = null): void
    {
        $plugin = Plugin::query()->firstOrNew(['slug' => $slug]);

        $attributes = [
            'name' => $manifest?->name ?? $plugin->name ?? $slug,
            'version' => $manifest?->version ?? $plugin->version ?? '1.0.0',
            'is_active' => false,
            'status' => PluginStatus::Failed,
            'last_error' => $error,
            'last_error_at' => now(),
        ];

        if ($manifest !== null) {
            $attributes['description'] = $manifest->description;
            $attributes['author'] = $manifest->author;
            $attributes['license'] = $manifest->license;
            $attributes['manifest'] = $manifest->toArray();
        }

        $plugin->fill($attributes)->save();

        $this->forgetCache();
    }

    public function delete(string $slug): void
    {
        Plugin::query()->where('slug', $slug)->delete();

        $this->forgetCache();
    }

    public function forgetCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }
}
