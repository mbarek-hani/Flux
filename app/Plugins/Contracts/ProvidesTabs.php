<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

interface ProvidesTabs
{
    /**
     * @return list<array{id: string, label: string, icon: string}>
     */
    public function tabs(): array;
}
