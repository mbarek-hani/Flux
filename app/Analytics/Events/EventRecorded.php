<?php

declare(strict_types=1);

namespace App\Analytics\Events;

use App\Analytics\Models\Event;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly Event $event,
        public readonly array $data = [],
        public readonly array $metadata = [],
    ) {}
}
