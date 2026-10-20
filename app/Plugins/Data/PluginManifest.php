<?php

declare(strict_types=1);

namespace App\Plugins\Data;

final readonly class PluginManifest
{
    /**
     * @param  list<HookDefinition>  $hooks
     * @param  list<array<string, mixed>>  $tabs
     * @param  list<array<string, mixed>>  $navigation
     * @param  list<array<string, mixed>>  $settings
     * @param  array<string, mixed>  $routes
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $version,
        public string $description,
        public string $author,
        public string $license,
        public ?string $url,
        public string $class,
        public string $namespace,
        public array $hooks,
        public array $tabs,
        public array $navigation,
        public array $settings,
        public array $routes,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $slug = $data['slug'] ?? null;
        $class = $data['class'] ?? null;

        if (! is_string($slug) || trim($slug) === '') {
            return null;
        }

        if (! is_string($class) || trim($class) === '') {
            return null;
        }

        $class = trim($class, '\\');
        $namespace = self::namespaceFromClass($class);

        if ($namespace === null) {
            return null;
        }

        $hooks = [];

        foreach ((array) ($data['hooks'] ?? []) as $hook) {
            $definition = is_string($hook)
                ? HookDefinition::render($hook)
                : HookDefinition::fromArray((array) $hook);

            if ($definition !== null) {
                $hooks[] = $definition;
            }
        }

        return new self(
            slug: trim($slug),
            name: (string) ($data['name'] ?? $slug),
            version: (string) ($data['version'] ?? '1.0.0'),
            description: (string) ($data['description'] ?? ''),
            author: (string) ($data['author'] ?? 'Unknown'),
            license: (string) ($data['license'] ?? 'Unspecified'),
            url: isset($data['url']) && is_string($data['url']) ? $data['url'] : null,
            class: $class,
            namespace: $namespace,
            hooks: $hooks,
            tabs: self::arrayOfArrays($data['tabs'] ?? []),
            navigation: self::arrayOfArrays($data['navigation'] ?? []),
            settings: self::arrayOfArrays($data['settings'] ?? []),
            routes: is_array($data['routes'] ?? null) ? $data['routes'] : [],
            raw: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @return array{web?: array<string, mixed>, api?: array<string, mixed>}
     */
    public function routeConfiguration(): array
    {
        $routes = $this->routes;
        $configuration = [];

        if (isset($routes['web']) && is_array($routes['web'])) {
            $configuration['web'] = $routes['web'];
        }

        if (isset($routes['api']) && is_array($routes['api'])) {
            $configuration['api'] = $routes['api'];
        }

        return $configuration;
    }

    public static function namespaceFromClass(string $class): ?string
    {
        $parts = explode('\\', trim($class, '\\'));

        if (count($parts) < 2 || $parts[0] !== 'Plugins') {
            return null;
        }

        return $parts[0].'\\'.$parts[1];
    }

    /**
     * @param  mixed  $value
     * @return list<array<string, mixed>>
     */
    private static function arrayOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            fn (mixed $item): bool => is_array($item),
        ));
    }
}
