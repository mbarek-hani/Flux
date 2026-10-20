<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Evenement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvenementEnregistre
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $donnees  Données de l'événement
     * @param  array<string, mixed>  $metadonnees  Métadonnées sérialisables de la requête (IP, User-Agent, referer, etc.)
     */
    public function __construct(
        public readonly Evenement $evenement,
        public readonly array $donnees = [],
        public readonly array $metadonnees = [],
    ) {}
}
