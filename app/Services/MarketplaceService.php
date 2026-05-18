<?php

namespace App\Services;

use App\Core\Plugin\PluginManager;
use App\Models\Plugin;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class MarketplaceService
{
    private const string ERROR = "Impossible de se connecter au registre de plugins. Veuillez vérifier votre connexion ou réessayer plus tard.";
    private string $registryUrl;

    public function __construct()
    {
        $this->registryUrl = config('services.fluxHUB.url');
    }

    /**
     * Obtenir la liste des plugins du registre (paginée et filtrée).
     */
    public function getPlugins(
        string $search = '',
        int $page = 1,
        int $pageSize = 10,
    ): array {
        try {
            $response = Http::timeout(5)->get(
                "{$this->registryUrl}/v1/marketplace/plugins",
                [
                    'search' => $search,
                    'page' => $page,
                    'page_size' => $pageSize,
                ],
            );

            if ($response->successful()) {
                return $response->json();
            }
            throw new \Exception("Code HTTP " . $response->status() . " retourné par le registre");
        } catch (\Throwable $e) {
            Log::error(
                'Erreur de communication avec le registre : '.
                    $e->getMessage(),
            );
            throw new \Exception(static::ERROR);
        }
    }

    /**
     * Obtenir les détails d'un plugin.
     */
    public function getPluginDetails(string $id): array
    {
        try {
            $response = Http::timeout(5)->get(
                "{$this->registryUrl}/v1/marketplace/plugins/{$id}",
            );
            if ($response->successful()) {
                return $response->json('data');
            }
            throw new \Exception("Code HTTP " . $response->status() . " retourné par le registre");
        } catch (\Throwable $e) {
            Log::error('Erreur détails plugin : '.$e->getMessage());
            throw new \Exception(static::ERROR);
        }
    }

    /**
     * Installer ou mettre à jour un plugin.
     */
    public function installerPlugin(
        PluginManager $manager,
        string $pluginId,
        callable $onProgress,
    ): bool {
        // Étape 1 : Connexion au registre
        $onProgress('Connexion au registre FluxHUB...', 10);

        // Étape 2 : Récupérer la clé publique
        $onProgress('Récupération de la clé publique de signature...', 20);
        try {
            $keyResponse = Http::timeout(10)->get(
                "{$this->registryUrl}/v1/public-key",
            );
            if (! $keyResponse->successful()) {
                throw new Exception(
                    'Impossible de récupérer la clé publique du registre',
                );
            }
            $publicKeyPEM = $keyResponse->body();
        } catch (\Throwable $e) {
            throw new Exception(
                'Échec de la récupération de la clé publique : '.
                    $e->getMessage(),
            );
        }

        // Étape 3 : Télécharger l'archive du plugin
        $onProgress("Téléchargement de l'archive du plugin...", 40);
        try {
            $downloadResponse = Http::timeout(30)->get(
                "{$this->registryUrl}/v1/marketplace/plugins/{$pluginId}/download",
            );
            if (! $downloadResponse->successful()) {
                throw new Exception(
                    'Impossible de télécharger le package du plugin : Code '.
                        $downloadResponse->status(),
                );
            }
            $zipPayload = $downloadResponse->body();
        } catch (\Throwable $e) {
            throw new Exception(
                'Échec du téléchargement du package : '.$e->getMessage(),
            );
        }

        // Étape 4 : Extraction des en-têtes
        $onProgress('Vérification des en-têtes de sécurité...', 60);
        $serverHash = $downloadResponse->header('X-Plugin-SHA256');
        $serverSignature = $downloadResponse->header('X-Plugin-Signature');
        $pluginName = $downloadResponse->header('X-Plugin-Name');
        $pluginVersion = $downloadResponse->header('X-Plugin-Version');

        if (! $serverHash || ! $serverSignature) {
            throw new Exception(
                'En-têtes de sécurité cryptographique manquants dans la réponse du registre',
            );
        }

        // Étape 5 : Validation SHA256
        $onProgress("Vérification de l'intégrité SHA-256...", 75);
        $localHash = hash('sha256', $zipPayload);
        if (! hash_equals($serverHash, $localHash)) {
            throw new Exception(
                "Échec de la vérification d'intégrité : L'empreinte locale ne correspond pas aux métadonnées du serveur.",
            );
        }

        // Étape 6 : Validation Signature
        $onProgress('Vérification de la signature RSA cryptographique...', 85);
        $publicKey = openssl_pkey_get_public($publicKeyPEM);
        if (! $publicKey) {
            throw new Exception(
                'Impossible de charger la clé publique RSA du registre',
            );
        }
        $signatureBytes = base64_decode($serverSignature);
        $verifyResult = openssl_verify(
            $zipPayload,
            $signatureBytes,
            $publicKey,
            OPENSSL_ALGO_SHA256,
        );
        if ($verifyResult !== 1) {
            throw new Exception(
                'Échec de la vérification cryptographique : La signature est invalide ou le fichier a été altéré !',
            );
        }

        // Étape 7 : Extraction zip
        $onProgress(
            'Décompression et installation des fichiers du plugin...',
            90,
        );

        // Dossier temporaire pour l'extraction
        $tempDir = storage_path('app/plugins/extracted_tmp_'.$pluginId);
        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }
        File::makeDirectory($tempDir, 0755, true, true);

        $tempZipPath = $tempDir.'/package.zip';
        File::put($tempZipPath, $zipPayload);

        $zip = new ZipArchive;
        if ($zip->open($tempZipPath) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
        } else {
            File::deleteDirectory($tempDir);
            throw new Exception(
                "Impossible d'ouvrir l'archive zip téléchargée",
            );
        }
        File::delete($tempZipPath);

        // Trouver le manifest.json
        $manifestPath = null;
        $extractedFilesDir = $tempDir;

        if (File::exists($tempDir.'/manifest.json')) {
            $manifestPath = $tempDir.'/manifest.json';
        } else {
            // Chercher dans les sous-dossiers
            $dirs = File::directories($tempDir);
            if (
                count($dirs) === 1 &&
                File::exists($dirs[0].'/manifest.json')
            ) {
                $manifestPath = $dirs[0].'/manifest.json';
                $extractedFilesDir = $dirs[0];
            }
        }

        if (! $manifestPath) {
            File::deleteDirectory($tempDir);
            throw new Exception(
                "Le fichier 'manifest.json' est introuvable dans le package du plugin",
            );
        }

        // Lire le manifest pour extraire la classe
        $manifestContent = json_decode(File::get($manifestPath), true);
        if (! $manifestContent || ! isset($manifestContent['classe'])) {
            File::deleteDirectory($tempDir);
            throw new Exception(
                "Le fichier 'manifest.json' est invalide ou ne contient pas la clé 'classe'",
            );
        }

        // Déterminer le dossier cible
        $classePlugin = $manifestContent['classe'];
        $parties = explode('\\', trim($classePlugin, '\\'));
        if (count($parties) < 2 || $parties[0] !== 'Plugins') {
            File::deleteDirectory($tempDir);
            throw new Exception(
                'Namespace du plugin invalide : '.$classePlugin,
            );
        }

        $folderName = $parties[1];
        $targetDir = base_path('plugins/'.$folderName);

        // Si le dossier existe déjà (mise à jour), on le supprime
        if (File::exists($targetDir)) {
            File::deleteDirectory($targetDir);
        }

        // S'assurer que le dossier base_path('plugins') existe
        if (! File::exists(base_path('plugins'))) {
            File::makeDirectory(base_path('plugins'), 0755, true);
        }

        // Déplacer les fichiers du plugin
        File::moveDirectory($extractedFilesDir, $targetDir, true);
        File::deleteDirectory($tempDir);

        // Étape 8 : Enregistrer en BDD
        $onProgress('Enregistrement en base de données...', 91);

        $pluginIdentifiant = $manifestContent['identifiant'] ?? $folderName;

        // Supprimer l'ancien enregistrement s'il existe (pour le cas des mises à jour)
        Plugin::where('identifiant', $pluginIdentifiant)->delete();

        $plugin = Plugin::create([
            'identifiant' => $pluginIdentifiant,
            'nom' => $manifestContent['nom'] ?? $pluginName,
            'version' => $manifestContent['version'] ?? $pluginVersion,
            'description' => $manifestContent['description'] ?? '',
            'auteur' => $manifestContent['auteur'] ?? $manifestContent['author'] ?? 'Inconnu',
            'actif' => true,
            'metadonnees' => $manifestContent,
            'installe_le' => now(),
        ]);

        // Réinitialiser le gestionnaire pour découvrir le nouveau plugin sur le disque
        $manager->initialiser();

        $manager->installer($plugin->identifiant);

        $onProgress('Installation réussie !', 100);

        return true;
    }

    /**
     * Désinstaller un plugin.
     */
    public function desinstallerPlugin(
        PluginManager $manager,
        string $pluginId,
        callable $onProgress,
    ): bool {
        $onProgress('Recherche du plugin dans le système...', 15);
        $remoteDetails = $this->getPluginDetails($pluginId);
        if ($remoteDetails && isset($remoteDetails['name'])) {
            $plugin = Plugin::where('identifiant', $remoteDetails['name'])->first();
        }
        if (! $plugin) {
            throw new Exception(
                "Le plugin n'est pas répertorié dans le système",
            );
        }

        // Étape 1 : Désinstallation du plugin dans le manager
        $onProgress('Désinstallation du plugin...', 40);
        $manager->desinstaller($plugin->identifiant);

        // Étape 2 : Suppression des fichiers du plugin
        $onProgress('Suppression des fichiers du plugin...', 75);
        $folderName = null;
        if (isset($plugin->metadonnees['classe'])) {
            $parties = explode(
                '\\',
                trim($plugin->metadonnees['classe'], '\\'),
            );
            if (count($parties) >= 2) {
                $folderName = $parties[1];
            }
        }

        if ($folderName) {
            $targetDir = base_path('plugins/'.$folderName);
            if (File::exists($targetDir)) {
                File::deleteDirectory($targetDir);
            }
        }

        // Étape 3 : Mise à jour de la base de données
        $onProgress('Nettoyage de la base de données...', 95);
        $plugin->delete();

        $onProgress('Désinstallation réussie !', 100);

        return true;
    }
    
    public function enrichPlugin(array $remote, ?Plugin $local): array
    {
        $installed = $local !== null && $local->installe_le !== null;
    
        $installedVersion = $installed
            ? $this->normalizeVersion($local->version)
            : null;
    
        $currentVersion = $this->normalizeVersion(
            $remote['current_version']
        );
    
        return [
            'id' => $remote['id'],
            'nom' => $remote['name'],
            'author' => $remote['author'] ?? 'Inconnu',
            'description' => $remote['description'] ?? '',
            'current_version' => $currentVersion,
            'total_downloads' => $remote['total_downloads'] ?? 0,
            'repo_url' => $remote['repo_url'] ?? null,
    
            'status' => $installed
                ? 'installed'
                : 'not_installed',
    
            'installed_version' => $installedVersion,
    
            'update_available' => $installed &&
                $this->versionWithoutPrefix($installedVersion) !==
                $this->versionWithoutPrefix($currentVersion),
    
            'actif' => $local?->actif ?? false,
        ];
    }
    
    public function normalizeVersion(?string $version): ?string
    {
        if (!$version) {
            return null;
        }
    
        return str_starts_with(strtolower($version), 'v')
            ? $version
            : 'v' . $version;
    }
    
    public function versionWithoutPrefix(?string $version): ?string
    {
        return $version
            ? ltrim($version, 'vV')
            : null;
    }
    
    public function defaultMeta(): array
    {
        return [
            'total' => 0,
            'page' => 1,
            'page_size' => 10,
            'total_pages' => 1,
        ];
    }
}
