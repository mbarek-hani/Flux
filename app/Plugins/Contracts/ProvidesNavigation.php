<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

interface ProvidesNavigation
{
    /**
     * @return list<array{label: string, route?: string, icon?: string, children?: list<array{label: string, route: string}>}>
     */
    public function navigation(): array;
}
