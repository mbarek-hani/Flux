<?php

namespace App\Core\Plugin;

use App\Models\Evenement;
use App\Models\Plugin as PluginModele;
use App\Models\Visite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PluginManager
{
    /** @var array<string, PluginInterface> */
    private array $pluginsDecouverts = [];

    /** @var array<string, PluginInterface> */
    private array $pluginsActifs = [];

    /** @var array<string, PluginInterface[]> */
    private array $hooks = [];

    public function __construct(private PluginDiscovery $discovery) {}

    public function initialiser(): void
    {
        if (! $this->tableExiste()) {
            return;
        }

        $this->pluginsDecouverts = $this->discovery->decouvrir();
        $actifs = $this->getActifsEnBdd();

        foreach ($this->pluginsDecouverts as $id => $plugin) {
            if (in_array($id, $actifs)) {
                $this->chargerPlugin($plugin);
            }
        }
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
            return PluginModele::where('actif', true)
                ->pluck('identifiant')
                ->toArray();
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
            Log::error(
                "Plugin [{$plugin->getIdentifiant()}] : ".$e->getMessage(),
            );
        }
    }

    public function activer(string $id): bool
    {
        $plugin = $this->pluginsDecouverts[$id] ?? null;
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
                    'metadonnees' => $plugin->getManifest(),
                ],
            );

            $this->chargerPlugin($plugin);

            return true;
        } catch (\Throwable $e) {
            Log::error("Activation [{$id}] : ".$e->getMessage());

            return false;
        }
    }

    public function desactiver(string $id): bool
    {
        $plugin = $this->pluginsActifs[$id] ?? null;
        if (! $plugin) {
            return false;
        }

        try {
            $plugin->desactiver();
            PluginModele::where('identifiant', $id)->update(['actif' => false]);
            unset($this->pluginsActifs[$id]);

            return true;
        } catch (\Throwable $e) {
            Log::error("Désactivation [{$id}] : ".$e->getMessage());

            return false;
        }
    }

    public function installer(string $id): void
    {
        $plugin = $this->pluginsDecouverts[$id] ?? null;
        $plugin->installer();
        $this->activer($id);
    }

    public function desinstaller(string $id): void
    {
        $plugin = $this->pluginsActifs[$id] ?? $this->pluginsDecouverts[$id] ?? null;
        $this->desactiver($id);
        $plugin->desinstaller();

        PluginModele::where('identifiant', $id)->delete();
    }

    // Hooks

    /** @return string[] */
    public function executerHook(string $hook, array $donnees = []): array
    {
        $resultats = [];

        foreach ($this->hooks[$hook] ?? [] as $plugin) {
            $resultats[] = $plugin->rendrePourHook($hook, $donnees); // now PluginInterface has rendrePourHook
        }

        return $resultats;
    }

    // Tracking Pipeline

    /**
     * Passe les données de tracking à travers tous les plugins actifs.
     * Chaque plugin peut enrichir les données.
     */
    public function enrichirTracking(array $donnees, Request $request): array
    {
        foreach ($this->pluginsActifs as $plugin) {
            $resultat = $plugin->enrichirTracking($donnees, $request);
            if ($resultat !== null) {
                $donnees = $resultat;
            }
        }

        return $donnees;
    }

    /**
     * Notifie tous les plugins après l'enregistrement d'une visite.
     */
    public function apresVisite(Visite $visite, array $donnees, $request): void
    {
        foreach ($this->pluginsActifs as $plugin) {
            try {
                $plugin->apresVisite($visite, $donnees, $request);
            } catch (\Throwable $e) {
                Log::error(
                    "Plugin [{$plugin->getIdentifiant()}] apresVisite : ".
                    $e->getMessage(),
                );
            }
        }
    }

    /**
     * Notifie tous les plugins après l'enregistrement d'un événement.
     */
    public function apresEvenement(
        Evenement $evenement,
        array $donnees,
        $request,
    ): void {
        foreach ($this->pluginsActifs as $plugin) {
            try {
                $plugin->apresEvenement($evenement, $donnees, $request);
            } catch (\Throwable $e) {
                Log::error(
                    "Plugin [{$plugin->getIdentifiant()}] apresEvenement : ".
                    $e->getMessage(),
                );
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

    /** @return array<string, array> */
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

    /** @return array<string, PluginInterface> */
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
