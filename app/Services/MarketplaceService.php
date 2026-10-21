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
    private const string ERROR = 'Unable to connect to the plugin registry. Check your connection and try again later.';

    private string $registryUrl;

    public function __construct()
    {
        $this->registryUrl = config('services.fluxHUB.url');
    }

    /**
     * Get the paginated and filtered plugin list from the registry.
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
            throw new Exception('The registry returned HTTP status '.$response->status());
        } catch (\Throwable $e) {
            Log::error(
                'Unable to communicate with the registry: '.
                    $e->getMessage(),
            );
            throw new Exception(static::ERROR);
        }
    }

    /**
     * Get plugin details.
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
            throw new Exception('The registry returned HTTP status '.$response->status());
        } catch (\Throwable $e) {
            Log::error('Unable to load plugin details: '.$e->getMessage());
            throw new Exception(static::ERROR);
        }
    }

    /**
     * Install or update a plugin.
     */
    public function installerPlugin(
        PluginManager $manager,
        string $pluginId,
        callable $onProgress,
    ): bool {
        $localPlugin = Plugin::where('identifiant', $pluginId)->first();
        $isUpdate = $localPlugin && $localPlugin->installe_le !== null;
        $actionName = $isUpdate ? 'update' : 'installation';

        // Step 1: Connect to the registry
        $onProgress('Connecting to the FluxHUB registry...', 10);

        // Step 2: Retrieve the public key
        $onProgress('Retrieving the signing public key...', 20);
        try {
            $keyResponse = Http::timeout(10)->get(
                "{$this->registryUrl}/v1/public-key",
            );
            if (! $keyResponse->successful()) {
                throw new Exception(
                    'Unable to retrieve the registry public key.',
                );
            }
            $publicKeyPEM = $keyResponse->body();
        } catch (\Throwable $e) {
            throw new Exception(
                'Unable to retrieve the public key: '.
                    $e->getMessage(),
            );
        }

        // Step 3: Download the plugin archive
        $onProgress("Downloading the archive for {$actionName}...", 40);
        try {
            $downloadResponse = Http::timeout(30)->get(
                "{$this->registryUrl}/v1/marketplace/plugins/{$pluginId}/download",
            );
            if (! $downloadResponse->successful()) {
                throw new Exception(
                    'Unable to download the plugin package. HTTP status: '.
                        $downloadResponse->status(),
                );
            }
            $zipPayload = $downloadResponse->body();
        } catch (\Throwable $e) {
            throw new Exception(
                'Unable to download the package: '.$e->getMessage(),
            );
        }

        // Step 4: Read the response headers
        $onProgress('Checking security headers...', 60);
        $serverHash = $downloadResponse->header('X-Plugin-SHA256');
        $serverSignature = $downloadResponse->header('X-Plugin-Signature');
        $pluginName = $downloadResponse->header('X-Plugin-Name');
        $pluginVersion = $downloadResponse->header('X-Plugin-Version');

        if (! $serverHash || ! $serverSignature) {
            throw new Exception(
                'The registry response is missing cryptographic security headers.',
            );
        }

        // Step 5: Validate the SHA-256 hash
        $onProgress('Verifying SHA-256 integrity...', 75);
        $localHash = hash('sha256', $zipPayload);
        if (! hash_equals($serverHash, $localHash)) {
            throw new Exception(
                'Integrity check failed: the local hash does not match the server metadata.',
            );
        }

        // Step 6: Validate the signature
        $onProgress('Verifying the cryptographic RSA signature...', 85);
        $publicKey = openssl_pkey_get_public($publicKeyPEM);
        if (! $publicKey) {
            throw new Exception(
                'Unable to load the registry RSA public key.',
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
                'Cryptographic verification failed: the signature is invalid or the file has been tampered with.',
            );
        }

        // Step 7: Extract the ZIP archive
        $onProgress(
            $isUpdate ? 'Extracting and applying the plugin file update...' : 'Extracting and installing the plugin files...',
            90,
        );

        // Temporary extraction directory
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
                'Unable to open the downloaded ZIP archive.',
            );
        }
        File::delete($tempZipPath);

        // Locate manifest.json
        $manifestPath = null;
        $extractedFilesDir = $tempDir;

        if (File::exists($tempDir.'/manifest.json')) {
            $manifestPath = $tempDir.'/manifest.json';
        } else {
            // Search the subdirectories
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
                "The plugin package does not contain a 'manifest.json' file.",
            );
        }

        // Read the manifest to extract the plugin class
        $manifestContent = json_decode(File::get($manifestPath), true);
        if (! $manifestContent || ! isset($manifestContent['classe'])) {
            File::deleteDirectory($tempDir);
            throw new Exception(
                "The 'manifest.json' file is invalid or does not contain the 'classe' key.",
            );
        }

        // Determine the target directory
        $pluginClass = $manifestContent['classe'];
        $parts = explode('\\', trim($pluginClass, '\\'));
        if (count($parts) < 2 || $parts[0] !== 'Plugins') {
            File::deleteDirectory($tempDir);
            throw new Exception(
                'Invalid plugin namespace: '.$pluginClass,
            );
        }

        $folderName = $parts[1];
        $targetDir = base_path('plugins/'.$folderName);

        $pluginIdentifier = $manifestContent['identifiant'] ?? $folderName;
        $runMigrations = true;

        if ($isUpdate && $localPlugin) {
            $oldVersion = $localPlugin->version;
            $newVersion = $manifestContent['version'] ?? $pluginVersion;
            
            if ($this->hasMajorVersionChange($oldVersion, $newVersion)) {
                $onProgress('Major update detected. Removing legacy data...', 88);
                $manager->desinstaller($pluginIdentifier);
            } else {
                $runMigrations = false;
            }
        }

        // Remove the existing directory before an update
        if (File::exists($targetDir)) {
            File::deleteDirectory($targetDir);
        }

        // Ensure the base plugins directory exists
        if (! File::exists(base_path('plugins'))) {
            File::makeDirectory(base_path('plugins'), 0755, true);
        }

        // Move the plugin files into place
        File::moveDirectory($extractedFilesDir, $targetDir, true);
        File::deleteDirectory($tempDir);

        // Step 8: Persist the plugin state
        $onProgress($isUpdate ? 'Updating the database...' : 'Saving to the database...', 91);

        // Remove the previous record for non-major updates
        Plugin::where('identifiant', $pluginIdentifier)->delete();

        $plugin = Plugin::create([
            'identifiant' => $pluginIdentifier,
            'nom' => $manifestContent['nom'] ?? $pluginName,
            'version' => $manifestContent['version'] ?? $pluginVersion,
            'description' => $manifestContent['description'] ?? '',
            'auteur' => $manifestContent['auteur'] ?? $manifestContent['author'] ?? 'Unknown',
            'actif' => true,
            'metadonnees' => $manifestContent,
            'installe_le' => now(),
        ]);

        // Reload the manager so it discovers the plugin on disk
        $manager->initialiser();

        $manager->installer($plugin->identifiant, $runMigrations);

        $onProgress($isUpdate ? 'Update completed successfully.' : 'Installation completed successfully.', 100);

        return true;
    }

    /**
     * Uninstall a plugin.
     */
    public function desinstallerPlugin(
        PluginManager $manager,
        string $pluginId,
        callable $onProgress,
    ): bool {
        $onProgress('Looking up the plugin in the system...', 15);
        $remoteDetails = $this->getPluginDetails($pluginId);
        if ($remoteDetails && isset($remoteDetails['id'])) {
            $plugin = Plugin::where('identifiant', $remoteDetails['id'])->first();
        }
        if (! $plugin) {
            throw new Exception(
                'The plugin is not registered in the system.',
            );
        }

        // Step 1: Uninstall the plugin through the manager
        $onProgress('Uninstalling the plugin...', 40);
        $manager->desinstaller($plugin->identifiant);

        // Step 2: Remove the plugin files
        $onProgress('Removing plugin files...', 75);
        $folderName = null;
        if (isset($plugin->metadonnees['classe'])) {
            $parts = explode(
                '\\',
                trim($plugin->metadonnees['classe'], '\\'),
            );
            if (count($parts) >= 2) {
                $folderName = $parts[1];
            }
        }

        if ($folderName) {
            $targetDir = base_path('plugins/'.$folderName);
            if (File::exists($targetDir)) {
                File::deleteDirectory($targetDir);
            }
        }

        // Step 3: Update the database
        $onProgress('Cleaning up the database...', 95);
        $plugin->delete();

        $onProgress('Uninstallation completed successfully.', 100);

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
            'author' => $remote['author'] ?? 'Unknown',
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
        if (! $version) {
            return null;
        }

        return str_starts_with(strtolower($version), 'v')
            ? $version
            : 'v'.$version;
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

    private function hasMajorVersionChange(?string $v1, ?string $v2): bool
    {
        if (! $v1 || ! $v2) {
            return false;
        }

        $clean1 = ltrim(strtolower($v1), 'v');
        $clean2 = ltrim(strtolower($v2), 'v');

        $parts1 = explode('.', $clean1);
        $parts2 = explode('.', $clean2);

        $m1 = (int) ($parts1[0] ?? 0);
        $m2 = (int) ($parts2[0] ?? 0);

        return $m1 !== $m2;
    }
}

