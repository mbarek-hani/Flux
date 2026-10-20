<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

use App\Plugins\Data\HookDefinition;

interface RegistersHooks
{
    /**
     * @return list<HookDefinition>
     */
    public function hooks(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function renderHook(string $hook, array $data = []): string;
}
