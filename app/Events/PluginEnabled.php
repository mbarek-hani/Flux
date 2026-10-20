<?php

declare(strict_types=1);

namespace App\Events;

use App\Plugins\Contracts\Plugin;
use Illuminate\Foundation\Events\Dispatchable;

class PluginEnabled
{
    use Dispatchable;

    public function __construct(
        public readonly Plugin $plugin,
        public readonly string $slug,
    ) {}
}
