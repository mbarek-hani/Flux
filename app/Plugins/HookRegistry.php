<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Plugins\Data\HookDefinition;
use App\Plugins\Contracts\FiltersData;
use App\Plugins\Contracts\Plugin;
use App\Plugins\Contracts\RegistersHooks;
use Illuminate\Support\Facades\Log;

final class HookRegistry
{
    /** @var array<string, list<array{priority: int, sequence: int, plugin: Plugin}>> */
    private array $renderHooks = [];

    /** @var array<string, list<array{priority: int, sequence: int, plugin: Plugin}>> */
    private array $filters = [];

    private int $sequence = 0;

    /**
     * @param  list<HookDefinition>  $hooks
     */
    public function register(Plugin $plugin, array $hooks): void
    {
        foreach ($hooks as $hook) {
            $bucket = $hook->type === 'filter' ? 'filters' : 'renderHooks';

            $this->{$bucket}[$hook->name][] = [
                'priority' => $hook->priority,
                'sequence' => $this->sequence++,
                'plugin' => $plugin,
            ];
        }
    }

    public function unregister(string $slug): void
    {
        foreach (['renderHooks', 'filters'] as $bucket) {
            foreach ($this->{$bucket} as $name => $entries) {
                $this->{$bucket}[$name] = array_values(array_filter(
                    $entries,
                    fn (array $entry): bool => $entry['plugin']->slug() !== $slug,
                ));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function render(string $name, array $data = []): array
    {
        $output = [];

        foreach ($this->sorted($this->renderHooks[$name] ?? []) as $entry) {
            $plugin = $entry['plugin'];

            if (! $plugin instanceof RegistersHooks) {
                continue;
            }

            try {
                $output[] = $plugin->renderHook($name, $data);
            } catch (\Throwable $exception) {
                Log::error("Plugin [{$plugin->slug()}] failed rendering hook [{$name}]: {$exception->getMessage()}");
            }
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function filter(string $name, mixed $value, array $context = []): mixed
    {
        foreach ($this->sorted($this->filters[$name] ?? []) as $entry) {
            $plugin = $entry['plugin'];

            if (! $plugin instanceof FiltersData) {
                continue;
            }

            try {
                $value = $plugin->filterData($name, $value, $context);
            } catch (\Throwable $exception) {
                Log::error("Plugin [{$plugin->slug()}] failed filtering [{$name}]: {$exception->getMessage()}");
            }
        }

        return $value;
    }

    public function clear(): void
    {
        $this->renderHooks = [];
        $this->filters = [];
        $this->sequence = 0;
    }

    /**
     * @param  list<array{priority: int, sequence: int, plugin: Plugin}>  $entries
     * @return list<array{priority: int, sequence: int, plugin: Plugin}>
     */
    private function sorted(array $entries): array
    {
        usort($entries, fn (array $a, array $b): int => [$a['priority'], $a['sequence']] <=> [$b['priority'], $b['sequence']]);

        return $entries;
    }
}
