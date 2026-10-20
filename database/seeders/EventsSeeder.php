<?php

namespace Database\Seeders;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvenementSeeder extends Seeder
{
    private array $types = [
        'clic',
        'formulaire',
        'telechargement',
        'inscription',
        'achat',
        'partage',
        'recherche',
        'video',
    ];

    private array $evenements = [
        'clic' => [
            ['nom' => 'Clic bouton inscription', 'donnees' => '{"bouton": "hero-cta"}'],
            ['nom' => 'Clic menu navigation', 'donnees' => '{"menu": "principal"}'],
            ['nom' => 'Clic lien externe', 'donnees' => '{"url": "https://github.com"}'],
            ['nom' => 'Clic bouton tarifs', 'donnees' => '{"plan": "pro"}'],
        ],
        'formulaire' => [
            ['nom' => 'Soumission formulaire contact', 'donnees' => '{"champs": 4}'],
            ['nom' => 'Soumission newsletter', 'donnees' => '{"source": "footer"}'],
            ['nom' => 'Soumission recherche', 'donnees' => '{"terme": "analytics"}'],
        ],
        'telechargement' => [
            ['nom' => 'Téléchargement PDF', 'donnees' => '{"fichier": "guide.pdf", "taille": "2.4MB"}'],
            ['nom' => 'Téléchargement brochure', 'donnees' => '{"fichier": "brochure.pdf", "taille": "1.1MB"}'],
        ],
        'inscription' => [
            ['nom' => 'Inscription gratuite', 'donnees' => '{"plan": "gratuit"}'],
            ['nom' => 'Inscription essai pro', 'donnees' => '{"plan": "pro", "duree": "14 jours"}'],
        ],
        'achat' => [
            ['nom' => 'Achat plan mensuel', 'donnees' => '{"plan": "pro", "montant": 29.99}'],
            ['nom' => 'Achat plan annuel', 'donnees' => '{"plan": "pro", "montant": 299.99}'],
            ['nom' => 'Achat addon', 'donnees' => '{"addon": "api-access", "montant": 9.99}'],
        ],
        'partage' => [
            ['nom' => 'Partage Twitter', 'donnees' => '{"reseau": "twitter"}'],
            ['nom' => 'Partage LinkedIn', 'donnees' => '{"reseau": "linkedin"}'],
            ['nom' => 'Partage Facebook', 'donnees' => '{"reseau": "facebook"}'],
        ],
        'recherche' => [
            ['nom' => 'Recherche interne', 'donnees' => '{"terme": "installation", "resultats": 5}'],
            ['nom' => 'Recherche interne', 'donnees' => '{"terme": "api", "resultats": 12}'],
            ['nom' => 'Recherche interne', 'donnees' => '{"terme": "prix", "resultats": 3}'],
        ],
        'video' => [
            ['nom' => 'Lecture vidéo démo', 'donnees' => '{"video": "demo-v2", "duree": 120}'],
            ['nom' => 'Lecture vidéo tutoriel', 'donnees' => '{"video": "tuto-install", "duree": 300}'],
        ],
    ];

    private array $chemins = [
        '/',
        '/about',
        '/contact',
        '/blog',
        '/blog/premier-article',
        '/pricing',
        '/features',
        '/docs',
    ];

    public function run(): void
    {
        $sites = Site::all();

        foreach ($sites as $site) {
            $this->genererEvenementsPourSite($site);
        }
    }

    private function genererEvenementsPourSite(Site $site): void
    {
        $evenements = [];
        $nombreEvenements = rand(80, 200);

        // Get visit IDs for this site
        $visiteIds = DB::table('visites')
            ->where('site_id', $site->id)
            ->pluck('id')
            ->toArray();

        for ($i = 0; $i < $nombreEvenements; $i++) {

            $type = $this->types[array_rand($this->types)];
            $details = $this->evenements[$type][array_rand($this->evenements[$type])];
            $dateEvenement = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $evenements[] = [
                'site_id' => $site->id,
                'visite_id' => ! empty($visiteIds) ? $visiteIds[array_rand($visiteIds)] : null,
                'session_id' => hash('sha256', Str::random(32)),
                'type' => $type,
                'nom' => $details['nom'],
                'donnees' => $details['donnees'],
                'chemin' => $this->chemins[array_rand($this->chemins)],
                'cree_le' => $dateEvenement,
            ];
        }

        // Insert in chunks of 50 for performance
        foreach (array_chunk($evenements, 50) as $chunk) {
            DB::table('evenements')->insert($chunk);
        }
    }
}
