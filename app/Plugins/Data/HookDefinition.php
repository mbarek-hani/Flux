<?php

declare(strict_types=1);

namespace App\Plugins\Data;

final readonly class HookDefinition
{
    private function __construct(
        public string $name,
        public string $type,
        public int $priority,
    ) {}

    public static function render(string $name, int $priority = 0): self
    {
        return new self($name, 'render', $priority);
    }

    public static function filter(string $name, int $priority = 0): self
    {
        return new self($name, 'filter', $priority);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $name = $data['name'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $type = $data['type'] ?? 'render';

        if (! in_array($type, ['render', 'filter'], true)) {
            return null;
        }

        return new self(
            trim($name),
            $type,
            (int) ($data['priority'] ?? 0),
        );
    }

    /**
     * @return array{name: string, type: string, priority: int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'priority' => $this->priority,
        ];
    }
}
