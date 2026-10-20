<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

interface FiltersData
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function filterData(string $name, mixed $value, array $context = []): mixed;
}
