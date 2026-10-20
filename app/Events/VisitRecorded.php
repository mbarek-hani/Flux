<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Visit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly Visit $visit,
        public readonly array $data = [],
        public readonly array $metadata = [],
    ) {}
}
