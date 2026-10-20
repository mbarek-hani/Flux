<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

interface RegistersListeners
{
    /**
     * @return array<class-string, list<class-string|callable>>
     */
    public function listeners(): array;
}
