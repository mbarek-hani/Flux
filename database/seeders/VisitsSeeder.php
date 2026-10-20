<?php

namespace Database\Seeders;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VisiteSeeder extends Seeder
{
    private array $navigateurs = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];

    private array $systemes = ['Windows', 'macOS', 'Linux', 'Android', 'iOS'];

    private array $appareils = ['ordinateur', 'mobile', 'tablette', 'inconnu'];

    private array $pays = [
        ['code' => 'FR', 'nom' => 'France'],
        ['code' => 'US', 'nom' => 'États-Unis'],
        ['code' => 'MA', 'nom' => 'Maroc'],
        ['code' => 'DE', 'nom' => 'Allemagne'],
        ['code' => 'GB', 'nom' => 'Royaume-Uni'],
        ['code' => 'CA', 'nom' => 'Canada'],
        ['code' => 'ES', 'nom' => 'Espagne'],
        ['code' => 'JP', 'nom' => 'Japon'],
    ];

    private array $chemins = [
        '/',
        '/about',
        '/contact',
        '/blog',
        '/blog/premier-article',
        '/blog/laravel-tips',
        '/blog/analytics-guide',
        '/pricing',
        '/features',
        '/docs',
        '/docs/installation',
        '/docs/api',
    ];

    private array $titres = [
        '/' => 'Accueil',
        '/about' => 'À propos',
        '/contact' => 'Contact',
        '/blog' => 'Blog',
        '/blog/premier-article' => 'Mon premier article',
        '/blog/laravel-tips' => 'Astuces Laravel',
        '/blog/analytics-guide' => 'Guide Analytics',
        '/pricing' => 'Tarifs',
        '/features' => 'Fonctionnalités',
        '/docs' => 'Documentation',
        '/docs/installation' => 'Installation',
        '/docs/api' => 'API Reference',
    ];

    private array $referents = [
        null,
        'https://google.com',
        'https://google.fr',
        'https://www.facebook.com',
        'https://twitter.com',
        'https://www.linkedin.com',
        'https://www.reddit.com',
        'https://github.com',
        'https://www.youtube.com',
    ];

    private array $referentsDomaines = [
        null,
        'google.com',
        'google.fr',
        'facebook.com',
        'twitter.com',
        'linkedin.com',
        'reddit.com',
        'github.com',
        'youtube.com',
    ];

    private array $utmSources = [null, 'google', 'facebook', 'twitter', 'newsletter', 'linkedin'];

    private array $utmMediums = [null, 'cpc', 'social', 'email', 'organic', 'referral'];

    private array $utmCampagnes = [null, 'lancement-v2', 'promo-ete', 'black-friday', 'newsletter-juin'];

    public function run(): void
    {
        $sites = Site::all();

        foreach ($sites as $site) {
            $this->genererVisitesPourSite($site);
        }
    }

    private function genererVisitesPourSite(Site $site): void
    {
        $visites = [];
        $nombreVisites = rand(150, 300);

        // Generate visits for the last 30 days
        for ($i = 0; $i < $nombreVisites; $i++) {

            $sessionId = hash('sha256', Str::random(32));
            $chemin = $this->chemins[array_rand($this->chemins)];
            $pays = $this->pays[array_rand($this->pays)];
            $refIndex = array_rand($this->referents);
            $dateVisite = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $visites[] = [
                'site_id' => $site->id,
                'session_id' => $sessionId,
                'url' => 'https://'.$site->domaine.$chemin,
                'chemin' => $chemin,
                'titre' => $this->titres[$chemin] ?? null,
                'referent' => $this->referents[$refIndex],
                'referent_domaine' => $this->referentsDomaines[$refIndex],
                'utm_source' => $this->utmSources[array_rand($this->utmSources)],
                'utm_medium' => $this->utmMediums[array_rand($this->utmMediums)],
                'utm_campagne' => $this->utmCampagnes[array_rand($this->utmCampagnes)],
                'navigateur' => $this->navigateurs[array_rand($this->navigateurs)],
                'version_navigateur' => (string) rand(90, 130),
                'systeme_exploitation' => $this->systemes[array_rand($this->systemes)],
                'appareil' => $this->appareils[array_rand($this->appareils)],
                'pays_code' => $pays['code'],
                'pays_nom' => $pays['nom'],
                'duree_session' => rand(5, 600),
                'est_rebond' => (bool) rand(0, 1),
                'est_nouvelle_session' => (bool) rand(0, 1),
                'cree_le' => $dateVisite,
            ];
        }

        // Insert in chunks of 50 for performance
        foreach (array_chunk($visites, 50) as $chunk) {
            DB::table('visites')->insert($chunk);
        }
    }
}
