<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Visite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisiteEnregistree
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $donnees  Données enrichies de tracking
     * @param  array<string, mixed>  $metadonnees  Métadonnées sérialisables de la requête (IP, User-Agent, referer, etc.)
     */
    public function __construct(
        public readonly Visite $visite,
        public readonly array $donnees = [],
        public readonly array $metadonnees = [],
    ) {}
}
