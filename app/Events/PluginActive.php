<?php

declare(strict_types=1);

namespace App\Events;

use App\Core\Plugin\PluginInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class PluginActive
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly PluginInterface $plugin,
        public readonly string $identifiant,
    ) {}
}
