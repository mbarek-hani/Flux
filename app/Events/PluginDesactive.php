<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class PluginDesactive
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $identifiant,
    ) {}
}
