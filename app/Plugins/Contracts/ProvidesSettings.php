<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

interface ProvidesSettings
{
    /**
     * @return list<array{key: string, label: string, type: string, required?: bool, help?: string, placeholder?: string, options?: list<string>}>
     */
    public function settings(): array;
}
