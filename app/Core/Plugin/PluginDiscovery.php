<?php

declare(strict_types=1);

namespace App\Core\Plugin;

use Illuminate\Support\Facades\Log;

/**
 * Scanne le dossier /plugins ou lit le cache pour découvrir les métadonnées
 * des plugins sans instancier leurs classes prématurément (lazy-loading).
 */
class PluginDiscovery
{
    private string $dossierPlugins;

    private string $cheminCache;

    public function __construct(
        private readonly PluginAutoloader $autoloader,
        ?string $dossierPlugins = null,
        ?string $cheminCache = null,
    ) {
        $this->dossierPlugins = $dossierPlugins ?? base_path('plugins');
        $this->cheminCache = $cheminCache ?? base_path('bootstrap/cache/plugins.php');
    }

    /**
     * Retourne la liste des métadonnées de tous les plugins valides détectés.
     *
     * @return array<string, PluginMetadata>
     */
    public function decouvrir(): array
    {
        if ($this->estEnCache()) {
            return $this->chargerDepuisCache();
        }

        return $this->scannerDossier();
    }

    /**
     * Scanne le système de fichiers pour lire les manifestes de plugins.
     * Enregistre les préfixes dans l'autoloader SANS instancier les classes.
     *
     * @return array<string, PluginMetadata>
     */
    public function scannerDossier(): array
    {
        $plugins = [];

        if (! is_dir($this->dossierPlugins)) {
            Log::warning("Le dossier plugins n'existe pas : {$this->dossierPlugins}");

            return $plugins;
        }

        $dossiers = glob($this->dossierPlugins.'/*', GLOB_ONLYDIR);
        if ($dossiers === false || empty($dossiers)) {
            return $plugins;
        }

        foreach ($dossiers as $dossier) {
            $metadata = $this->lireMetadataDossier($dossier);

            if ($metadata !== null) {
                // Enregistre le namespace dans l'autoloader
                $this->autoloader->addNamespace(
                    $metadata->namespace,
                    $metadata->cheminDossier.'/src',
                );

                $plugins[$metadata->identifiant] = $metadata;
            }
        }

        return $plugins;
    }

    /**
     * Instancie un plugin de manière différée (lazy-loading) et sécurisée.
     */
    public function instancier(PluginMetadata $metadata): ?PluginInterface
    {
        // 1. Charger les dépendances Vendor du plugin si présentes
        $this->autoloader->chargerVendor($metadata->cheminDossier);

        // 2. S'assurer que le namespace est enregistré
        $this->autoloader->addNamespace(
            $metadata->namespace,
            $metadata->cheminDossier.'/src',
        );

        // 3. Vérifier l'existence de la classe
        if (! class_exists($metadata->classe)) {
            Log::error("Classe plugin introuvable : {$metadata->classe}");

            return null;
        }

        // 4. Vérifier la conformité de l'interface
        if (! is_subclass_of($metadata->classe, AbstractPlugin::class)) {
            Log::error("La classe {$metadata->classe} n'hérite pas de AbstractPlugin");

            return null;
        }

        // 5. Instanciation sécurisée via le conteneur de services
        try {
            $instance = app($metadata->classe);

            if (! $instance instanceof PluginInterface) {
                return null;
            }

            return $instance;
        } catch (\Throwable $e) {
            Log::error("Échec d'instanciation du plugin [{$metadata->identifiant}] : ".$e->getMessage(), [
                'classe' => $metadata->classe,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Lit et parse le fichier manifest.json d'un dossier donné.
     */
    public function lireMetadataDossier(string $dossier): ?PluginMetadata
    {
        $cheminManifest = $dossier.'/manifest.json';

        if (! file_exists($cheminManifest)) {
            return null;
        }

        $contenu = file_get_contents($cheminManifest);
        if ($contenu === false) {
            Log::error("Impossible de lire le fichier {$cheminManifest}");

            return null;
        }

        $manifest = json_decode($contenu, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($manifest)) {
            Log::error("JSON invalide dans {$cheminManifest} : ".json_last_error_msg());

            return null;
        }

        return PluginMetadata::fromManifest($dossier, $manifest);
    }

    /**
     * Génère le fichier de cache compilé des plugins.
     *
     * @return array<string, PluginMetadata>
     */
    public function mettreEnCache(): array
    {
        $plugins = $this->scannerDossier();
        $cacheData = [];

        foreach ($plugins as $id => $metadata) {
            $cacheData[$id] = $metadata->toArray();
        }

        $dossierCache = dirname($this->cheminCache);
        if (! is_dir($dossierCache)) {
            mkdir($dossierCache, 0755, true);
        }

        file_put_contents(
            $this->cheminCache,
            '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($cacheData, true).';'.PHP_EOL,
        );

        return $plugins;
    }

    /**
     * Supprime le fichier de cache compilé.
     */
    public function viderCache(): bool
    {
        if (file_exists($this->cheminCache)) {
            return unlink($this->cheminCache);
        }

        return true;
    }

    public function estEnCache(): bool
    {
        return file_exists($this->cheminCache);
    }

    public function getCheminCache(): string
    {
        return $this->cheminCache;
    }

    public function getDossierPlugins(): string
    {
        return $this->dossierPlugins;
    }

    /**
     * Charge les plugins depuis le fichier de cache compilé.
     *
     * @return array<string, PluginMetadata>
     */
    private function chargerDepuisCache(): array
    {
        try {
            $cacheData = require $this->cheminCache;

            if (! is_array($cacheData)) {
                return $this->scannerDossier();
            }

            $plugins = [];

            foreach ($cacheData as $id => $data) {
                if (is_array($data)) {
                    $metadata = PluginMetadata::fromArray($data);

                    if ($metadata !== null) {
                        $this->autoloader->addNamespace(
                            $metadata->namespace,
                            $metadata->cheminDossier.'/src',
                        );

                        $plugins[$metadata->identifiant] = $metadata;
                    }
                }
            }

            return $plugins;
        } catch (\Throwable $e) {
            Log::error('Erreur lors du chargement du cache des plugins : '.$e->getMessage());

            return $this->scannerDossier();
        }
    }
}
