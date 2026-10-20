<?php

declare(strict_types=1);

namespace App\Plugins\Data;

final readonly class DiscoveredPlugin
{
    public function __construct(
        public PluginManifest $manifest,
        public string $path,
    ) {}

    public function slug(): string
    {
        return $this->manifest->slug;
    }

    public function name(): string
    {
        return $this->manifest->name;
    }

    public function version(): string
    {
        return $this->manifest->version;
    }

    public function namespace(): string
    {
        return $this->manifest->namespace;
    }

    public function class(): string
    {
        return $this->manifest->class;
    }

    /**
     * @return array{path: string, manifest: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'manifest' => $this->manifest->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $path = $data['path'] ?? null;
        $manifest = $data['manifest'] ?? null;

        if (! is_string($path) || ! is_array($manifest)) {
            return null;
        }

        $parsed = PluginManifest::fromArray($manifest);

        return $parsed === null ? null : new self($parsed, $path);
    }
}
