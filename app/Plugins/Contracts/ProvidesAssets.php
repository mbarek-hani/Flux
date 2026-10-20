<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

interface ProvidesAssets
{
    /**
     * @return list<string>
     */
    public function stylesheets(): array;

    /**
     * @return list<string>
     */
    public function scripts(): array;
}
