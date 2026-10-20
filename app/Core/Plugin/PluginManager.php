<?php

declare(strict_types=1);

namespace App\Core\Plugin;

use App\Events\PluginActive;
use App\Events\PluginDesactive;
use App\Models\Evenement;
use App\Models\Plugin as PluginModele;
use App\Models\Visite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PluginManager
{
    /** @var array<string, PluginMetadata> */
    private array $pluginsDecouverts = [];

    /** @var array<string, PluginInterface> */
    private array $pluginsActifs = [];

    /** @var array<string, PluginInterface[]> */
    private array $hooks = [];

    public function __construct(private readonly PluginDiscovery $discovery) {}

    public function initialiser(): void
    {
        if (! $this->tableExiste()) {
            return;
        }

        $this->pluginsDecouverts = $this->discovery->decouvrir();
        $actifs = $this->getActifsEnBdd();

        foreach ($actifs as $id) {
            if (isset($this->pluginsDecouverts[$id])) {
                $metadata = $this->pluginsDecouverts[$id];
                $plugin = $this->discovery->instancier($metadata);

                if ($plugin !== null) {
                    $this->chargerPlugin($plugin);
                }
            }
        }
    }

    /**
     * Réinitialise le gestionnaire en invalidant les caches et en rechargeant.
     */
    public function reinitialiser(): void
    {
        $this->discovery->viderCache();
        Cache::forget('plugins.actifs');
        $this->pluginsActifs = [];
        $this->hooks = [];
        $this->initialiser();
    }

    private function tableExiste(): bool
    {
        try {
            return Schema::hasTable('plugins');
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return string[] */
    private function getActifsEnBdd(): array
    {
        try {
            return Cache::rememberForever('plugins.actifs', function () {
                return PluginModele::where('actif', true)
                    ->pluck('identifiant')
                    ->toArray();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    private function chargerPlugin(PluginInterface $plugin): void
    {
        try {
            $plugin->enregistrer();

            foreach ($plugin->getHooks() as $hook) {
                $this->hooks[$hook][] = $plugin;
            }

            $this->pluginsActifs[$plugin->getIdentifiant()] = $plugin;
        } catch (\Throwable $e) {
            Log::error("Plugin [{$plugin->getIdentifiant()}] : ".$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    public function activer(string $id): bool
    {
        $metadata = $this->pluginsDecouverts[$id] ?? null;
        if (! $metadata) {
            return false;
        }

        $plugin = $this->pluginsActifs[$id] ?? $this->discovery->instancier($metadata);
        if (! $plugin) {
            return false;
        }

        try {
            $plugin->activer();

            PluginModele::updateOrCreate(
                ['identifiant' => $id],
                [
                    'nom' => $plugin->getNom(),
                    'version' => $plugin->getVersion(),
                    'actif' => true,
                    'active_le' => now(),
                    'installe_le' => now(),
                    'metadonnees' => $metadata->manifest,
                ],
            );

            Cache::forget('plugins.actifs');
            $this->chargerPlugin($plugin);

            PluginActive::dispatch($plugin, $id);

            return true;
        } catch (\Throwable $e) {
            Log::error("Activation [{$id}] : ".$e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }
    }

    public function desactiver(string $id): bool
    {
        $plugin = $this->pluginsActifs[$id] ?? null;
        if (! $plugin) {
            $metadata = $this->pluginsDecouverts[$id] ?? null;
            if ($metadata) {
                $plugin = $this->discovery->instancier($metadata);
            }
        }

        try {
            if ($plugin) {
                $plugin->desactiver();
            }

            PluginModele::where('identifiant', $id)->update(['actif' => false]);
            Cache::forget('plugins.actifs');
            unset($this->pluginsActifs[$id]);

            // Nettoyage des hooks associés à ce plugin
            foreach ($this->hooks as $hook => $plugins) {
                $this->hooks[$hook] = array_values(array_filter(
                    $plugins,
                    fn (PluginInterface $p) => $p->getIdentifiant() !== $id,
                ));
            }

            PluginDesactive::dispatch($id);

            return true;
        } catch (\Throwable $e) {
            Log::error("Désactivation [{$id}] : ".$e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }
    }

    public function installer(string $id, bool $runMigrations = true): void
    {
        $plugin = $this->getPluginInstance($id);
        if ($runMigrations && $plugin) {
            $plugin->installer();
        }
        $this->activer($id);
    }

    public function desinstaller(string $id): void
    {
        $plugin = $this->getPluginInstance($id);
        $this->desactiver($id);

        if ($plugin) {
            $plugin->desinstaller();
        }

        PluginModele::where('identifiant', $id)->delete();
        Cache::forget('plugins.actifs');
    }

    // Hooks

    /**
     * @param  array<string, mixed>  $donnees
     * @return string[]
     */
    public function executerHook(string $hook, array $donnees = []): array
    {
        $resultats = [];

        foreach ($this->hooks[$hook] ?? [] as $plugin) {
            try {
                $resultats[] = $plugin->rendrePourHook($hook, $donnees);
            } catch (\Throwable $e) {
                Log::error("Erreur lors de l'exécution du hook [{$hook}] par [{$plugin->getIdentifiant()}] : ".$e->getMessage());
            }
        }

        return $resultats;
    }

    // Tracking Pipeline

    /**
     * Passe les données de tracking à travers tous les plugins actifs.
     * Chaque plugin peut enrichir les données ou retourner null pour annuler le tracking.
     *
     * @param  array<string, mixed>  $donnees
     * @return array<string, mixed>|null
     */
    public function enrichirTracking(array $donnees, Request $request): ?array
    {
        foreach ($this->pluginsActifs as $plugin) {
            try {
                $resultat = $plugin->enrichirTracking($donnees, $request);

                // Si un plugin retourne explicitement null, le tracking est annulé
                if ($resultat === null) {
                    return null;
                }

                $donnees = $resultat;
            } catch (\Throwable $e) {
                Log::error("Plugin [{$plugin->getIdentifiant()}] enrichirTracking : ".$e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }

        return $donnees;
    }

    /**
     * Notifie tous les plugins après l'enregistrement d'une visite.
     *
     * @param  array<string, mixed>  $donnees
     */
    public function apresVisite(Visite $visite, array $donnees, Request $request): void
    {
        foreach ($this->pluginsActifs as $plugin) {
            try {
                $plugin->apresVisite($visite, $donnees, $request);
            } catch (\Throwable $e) {
                Log::error("Plugin [{$plugin->getIdentifiant()}] apresVisite : ".$e->getMessage());
            }
        }
    }

    /**
     * Notifie tous les plugins après l'enregistrement d'un événement.
     *
     * @param  array<string, mixed>  $donnees
     */
    public function apresEvenement(
        Evenement $evenement,
        array $donnees,
        Request $request,
    ): void {
        foreach ($this->pluginsActifs as $plugin) {
            try {
                $plugin->apresEvenement($evenement, $donnees, $request);
            } catch (\Throwable $e) {
                Log::error("Plugin [{$plugin->getIdentifiant()}] apresEvenement : ".$e->getMessage());
            }
        }
    }

    // UI Data

    /** @return array<int, array{id: string, label: string, icone: string, plugin: string}> */
    public function getOnglets(): array
    {
        $onglets = [];

        foreach ($this->pluginsActifs as $plugin) {
            foreach ($plugin->getOnglets() as $onglet) {
                $onglet['plugin'] = $plugin->getIdentifiant();
                $onglets[] = $onglet;
            }
        }

        return $onglets;
    }

    /** @return array<string, array{nom: string, champs: array}> */
    public function getTousLesReglages(): array
    {
        $reglages = [];

        foreach ($this->pluginsActifs as $plugin) {
            $champs = $plugin->getReglages();
            if (! empty($champs)) {
                $reglages[$plugin->getIdentifiant()] = [
                    'nom' => $plugin->getNom(),
                    'champs' => $champs,
                ];
            }
        }

        return $reglages;
    }

    /**
     * Retourne tous les liens de navigation des plugins actifs.
     *
     * @return array<int, array{label: string, route: string, icone: string, plugin: string}>
     */
    public function getNavigationItems(): array
    {
        $items = [];

        foreach ($this->pluginsActifs as $plugin) {
            foreach ($plugin->getNavigationItems() as $item) {
                $item['plugin'] = $plugin->getIdentifiant();
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Retourne les chemins CSS de tous les plugins actifs.
     *
     * @return string[]
     */
    public function getCssPaths(): array
    {
        $chemins = [];

        foreach ($this->pluginsActifs as $plugin) {
            $css = $plugin->getCssPath();
            if ($css) {
                $chemins[] = $css;
            }
        }

        return $chemins;
    }

    /**
     * Retourne le JavaScript de tracking de tous les plugins actifs.
     */
    public function getTrackerJavaScript(): string
    {
        $js = '';

        foreach ($this->pluginsActifs as $plugin) {
            $code = $plugin->getTrackerJavaScript();
            if ($code) {
                $js .= "\n// Plugin: {$plugin->getIdentifiant()}\n".$code;
            }
        }

        return $js;
    }

    // Accesseurs

    /**
     * Récupère un plugin actif.
     */
    public function getPlugin(string $id): ?PluginInterface
    {
        return $this->pluginsActifs[$id] ?? null;
    }

    /**
     * Récupère une instance de plugin (déjà active ou instanciée à la demande).
     */
    public function getPluginInstance(string $id): ?PluginInterface
    {
        if (isset($this->pluginsActifs[$id])) {
            return $this->pluginsActifs[$id];
        }

        if (isset($this->pluginsDecouverts[$id])) {
            return $this->discovery->instancier($this->pluginsDecouverts[$id]);
        }

        return null;
    }

    /**
     * Récupère les métadonnées d'un plugin découvert.
     */
    public function getPluginMetadata(string $id): ?PluginMetadata
    {
        return $this->pluginsDecouverts[$id] ?? null;
    }

    /** @return array<string, PluginMetadata> */
    public function getPluginsDecouverts(): array
    {
        return $this->pluginsDecouverts;
    }

    /** @return array<string, PluginInterface> */
    public function getPluginsActifs(): array
    {
        return $this->pluginsActifs;
    }

    public function estActif(string $id): bool
    {
        return isset($this->pluginsActifs[$id]);
    }
}
