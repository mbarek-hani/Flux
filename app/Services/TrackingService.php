<?php

namespace App\Services;

use App\Core\Plugin\PluginManager;
use App\Events\EvenementEnregistre;
use App\Events\VisiteEnregistree;
use App\Models\Evenement;
use App\Models\Site;
use App\Models\Visite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class TrackingService
{
    // Durée en secondes pour considérer une session comme nouvelle
    private const DUREE_SESSION = 1800; // 30 minutes

    /**
     * Enregistre une nouvelle visite de page.
     */
    public function enregistrerVisite(
        Site $site,
        array $donnees,
        Request $request,
    ): ?Visite {
        try {
            if ($site->ignorer_bots && $this->estUnBot($request->userAgent())) {
                return null;
            }

            // Pipeline plugins : enrichir les données AVANT stockage
            $manager = app(PluginManager::class);
            $donnees = $manager->enrichirTracking($donnees, $request);

            if ($donnees === null) {
                return null;
            }

            $sessionId = $this->genererSessionId($request, $site->id);

            $infoNavigateur = $this->parserUserAgent($request->userAgent());

            $estNouvelleSession = $this->verifierNouvelleSession($sessionId);

            $infoPays = $this->detecterPays($request);

            $visite = Visite::create([
                'site_id' => $site->id,
                'session_id' => $sessionId,
                'url' => $this->nettoyerUrl($donnees['url'] ?? ''),
                'chemin' => $this->nettoyerChemin($donnees['chemin'] ?? '/'),
                'titre' => $donnees['titre'] ?? null,
                'referent' => $donnees['referent'] ?? null,
                'referent_domaine' => $donnees['referent_domaine'] ?? null,
                'utm_source' => $donnees['utm_source'] ?? null,
                'utm_medium' => $donnees['utm_medium'] ?? null,
                'utm_campagne' => $donnees['utm_campagne'] ?? null,
                'navigateur' => $infoNavigateur['navigateur'],
                'version_navigateur' => $infoNavigateur['version'],
                'systeme_exploitation' => $infoNavigateur['systeme'],
                'appareil' => $donnees['appareil'] ?? $infoNavigateur['appareil'],
                'pays_code' => $infoPays['code'],
                'pays_nom' => $infoPays['nom'],
                'est_rebond' => true, // Mis à jour par mettreAJourDuree()
                'est_nouvelle_session' => $estNouvelleSession,
                'cree_le' => now(),
            ]);

            // Pipeline plugins : traitement APRÈS stockage
            $manager->apresVisite($visite, $donnees, $request);

            VisiteEnregistree::dispatch($visite, $donnees, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'site_id' => $site->id,
            ]);

            return $visite;
        } catch (\Throwable $e) {
            Log::error(
                'TrackingService::enregistrerVisite : '.$e->getMessage(),
            );

            return null;
        }
    }

    /**
     * Met à jour la durée d'une visite et le statut de rebond.
     */
    public function mettreAJourDuree(
        Site $site,
        array $donnees,
        Request $request,
    ): void {
        try {
            $sessionId = $this->genererSessionId($request, $site->id);
            $duree = (int) ($donnees['duree'] ?? 0);

            $visite = Visite::where('site_id', $site->id)
                ->where('session_id', $sessionId)
                ->where('chemin', $donnees['chemin'] ?? '/')
                ->latest('cree_le')
                ->first();

            if ($visite) {
                $visite->update([
                    'duree_session' => $duree,
                    'est_rebond' => $duree < 10, // < 10 secondes = rebond
                ]);
            }
        } catch (\Throwable $e) {
            Log::error(
                'TrackingService::mettreAJourDuree : '.$e->getMessage(),
            );
        }
    }

    /**
     * Enregistre un événement personnalisé.
     */
    public function enregistrerEvenement(
        Site $site,
        array $donnees,
        Request $request,
    ): ?Evenement {
        try {
            $sessionId = $this->genererSessionId($request, $site->id);

            // Pipeline plugins
            $manager = app(PluginManager::class);
            $donnees = $manager->enrichirTracking($donnees, $request);

            if ($donnees === null) {
                return null;
            }

            $visite = Visite::where('site_id', $site->id)
                ->where('session_id', $sessionId)
                ->latest('cree_le')
                ->first();

            $evenement = Evenement::create([
                'site_id' => $site->id,
                'visite_id' => $visite?->id,
                'session_id' => $sessionId,
                'type' => 'personnalise',
                'nom' => $donnees['nom'] ?? 'Événement',
                'donnees' => $donnees['donnees'] ?? null,
                'chemin' => $donnees['chemin'] ?? null,
                'cree_le' => now(),
            ]);

            // Pipeline plugins
            $manager->apresEvenement($evenement, $donnees, $request);

            EvenementEnregistre::dispatch($evenement, $donnees, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'site_id' => $site->id,
            ]);

            return $evenement;
        } catch (\Throwable $e) {
            Log::error(
                'TrackingService::enregistrerEvenement : '.$e->getMessage(),
            );

            return null;
        }
    }

    private function detecterPays(Request $request): array
    {
        try {
            $position = Location::get($request->ip());

            if ($position && $position->countryCode) {
                return [
                    'code' => strtoupper($position->countryCode),
                    'nom' => $position->countryName ?? $position->countryCode,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Géolocalisation échouée : '.$e->getMessage());
        }

        return ['code' => null, 'nom' => null];
    }

    /**
     * Génère un ID de session anonymisé.
     * Respect vie privée : on ne stocke JAMAIS l'IP brute.
     *
     * Hash = SHA256(IP + UserAgent + Date + SiteId + Sel)
     * → Différent chaque jour → impossible de tracker sur plusieurs jours
     */
    private function genererSessionId(Request $request, int $siteId): string
    {
        $ip = $request->ip() ?? '';
        $userAgent = $request->userAgent() ?? '';
        $date = now()->format('Y-m-d'); // Change chaque jour
        $sel = config('app.key'); // Sel secret de l'app

        return hash('sha256', $ip.$userAgent.$date.$siteId.$sel);
    }

    /**
     * Vérifie si c'est une nouvelle session
     * (aucune visite dans les 30 dernières minutes).
     */
    private function verifierNouvelleSession(string $sessionId): bool
    {
        return ! Visite::where('session_id', $sessionId)
            ->where('cree_le', '>=', now()->subSeconds(self::DUREE_SESSION))
            ->exists();
    }

    /**
     * Parse le User-Agent pour extraire navigateur, version, OS.
     * Simple et sans dépendance externe.
     *
     * @return array<string, string|null>
     */
    private function parserUserAgent(?string $userAgent): array
    {
        if (! $userAgent) {
            return [
                'navigateur' => null,
                'version' => null,
                'systeme' => null,
                'appareil' => 'inconnu',
            ];
        }

        $navigateur = 'Autre';
        $version = null;

        $navigateurs = [
            'Edg' => 'Edge',
            'OPR' => 'Opera',
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
        ];

        foreach ($navigateurs as $cle => $nom) {
            if (str_contains($userAgent, $cle)) {
                $navigateur = $nom;
                preg_match("/{$cle}\/([0-9]+)/", $userAgent, $matches);
                $version = $matches[1] ?? null;
                break;
            }
        }

        $systeme = 'Autre';

        $systemes = [
            'Windows NT 10' => 'Windows 10/11',
            'Windows NT 6' => 'Windows',
            'Mac OS X' => 'macOS',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iPadOS',
        ];

        foreach ($systemes as $cle => $nom) {
            if (str_contains($userAgent, $cle)) {
                $systeme = $nom;
                break;
            }
        }

        $appareil = 'ordinateur';

        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            $appareil = 'tablette';
        } elseif (
            preg_match('/mobile|iphone|ipod|android|blackberry/i', $userAgent)
        ) {
            $appareil = 'mobile';
        }

        return [
            'navigateur' => $navigateur,
            'version' => $version,
            'systeme' => $systeme,
            'appareil' => $appareil,
        ];
    }

    /**
     * Détecte si le User-Agent est un bot connu.
     */
    private function estUnBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return true;
        }

        $pattern =
            '/bot|crawl|spider|slurp|teoma|archive|track|'.
            'facebookexternalhit|Twitterbot|LinkedInBot|'.
            'WhatsApp|Googlebot|bingbot|yandex/i';

        return (bool) preg_match($pattern, $userAgent);
    }

    /**
     * Nettoie l'URL en supprimant les paramètres sensibles.
     */
    private function nettoyerUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        try {
            $parsed = parse_url($url);
            $base =
                ($parsed['scheme'] ?? 'https').
                '://'.
                ($parsed['host'] ?? '').
                ($parsed['path'] ?? '/');

            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $params);
                $utms = array_filter(
                    $params,
                    function ($cle) {
                        return str_starts_with($cle, 'utm_');
                    },
                    ARRAY_FILTER_USE_KEY,
                );

                if (! empty($utms)) {
                    $base .= '?'.http_build_query($utms);
                }
            }

            return substr($base, 0, 2048);
        } catch (\Throwable) {
            return substr($url, 0, 2048);
        }
    }

    /**
     * Nettoie et normalise le chemin URL.
     */
    private function nettoyerChemin(string $chemin): string
    {
        // Supprimer le trailing slash sauf pour "/"
        $chemin = rtrim($chemin, '/') ?: '/';

        return substr($chemin, 0, 1024);
    }
}
