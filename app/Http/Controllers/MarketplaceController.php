<?php

namespace App\Http\Controllers;

use App\Models\Plugin;
use App\Services\MarketplaceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceController extends Controller
{
    public function __construct(private MarketplaceService $service) {}

    /**
     * Liste et recherche les plugins du Marketplace.
     */
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);
        $error = null;

        try {
            $registryData = $this->service->getPlugins($search, $page);
            $remotePlugins = $registryData['data'] ?? [];
            $meta = $registryData['meta'] ?? [
                'total' => 0,
                'page' => 1,
                'page_size' => 20,
                'total_pages' => 1,
            ];
        } catch (\Throwable $e) {
            $remotePlugins = [];
            $meta = [
                'total' => 0,
                'page' => 1,
                'page_size' => 20,
                'total_pages' => 1,
            ];
            $error = "Impossible de se connecter au registre de plugins. Veuillez vérifier votre connexion ou réessayer plus tard.";
        }

        // Enrichir avec l'état local
        $plugins = [];
        foreach ($remotePlugins as $remote) {
            $local = Plugin::where('identifiant', $remote['name'])->first();

            $status = 'not_installed';
            $installedVersion = null;
            $updateAvailable = false;
            $actif = false;

            if ($local && $local->installe) {
                $status = 'installed';
                $installedVersion = $local->version;
                $actif = $local->actif;

                $remoteVer = ltrim($remote['current_version'], 'vV');
                $localVer = ltrim($local->version, 'vV');
                if ($localVer !== $remoteVer) {
                    $updateAvailable = true;
                }
            }

            $currentVer = $remote['current_version'];
            if (!str_starts_with(strtolower($currentVer), 'v')) {
                $currentVer = 'v' . $currentVer;
            }
            $installedVer = $installedVersion;
            if ($installedVer && !str_starts_with(strtolower($installedVer), 'v')) {
                $installedVer = 'v' . $installedVer;
            }

            $enriched = [
                'id' => $remote['id'],
                'nom' => $remote['name'],
                'author' => $remote['author'] ?? 'Inconnu',
                'description' => $remote['description'] ?? '',
                'current_version' => $currentVer,
                'total_downloads' => $remote['total_downloads'] ?? 0,
                'repo_url' => $remote['repo_url'] ?? null,
                'status' => $status,
                'installed_version' => $installedVer,
                'update_available' => $updateAvailable,
                'actif' => $actif,
            ];

            $plugins[] = $enriched;
        }

        return view('marketplace.index', compact('plugins', 'search', 'page', 'meta', 'error'));
    }

    /**
     * Affiche les détails d'un plugin du Marketplace.
     */
    public function show(string $id)
    {
        try {
            $remote = $this->service->getPluginDetails($id);
    
            $local = Plugin::where('identifiant', $remote['name'])->first();
    
            $status = 'not_installed';
            $installedVersion = null;
            $updateAvailable = false;
            $actif = false;
    
            if ($local && $local->installe) {
                $status = 'installed';
                $installedVersion = $local->version;
                $actif = $local->actif;
    
                $remoteVer = ltrim($remote['current_version'], 'vV');
                $localVer = ltrim($local->version, 'vV');
                if ($localVer !== $remoteVer) {
                    $updateAvailable = true;
                }
            }
    
            $currentVer = $remote['current_version'];
            if (!str_starts_with(strtolower($currentVer), 'v')) {
                $currentVer = 'v' . $currentVer;
            }
            $installedVer = $installedVersion;
            if ($installedVer && !str_starts_with(strtolower($installedVer), 'v')) {
                $installedVer = 'v' . $installedVer;
            }
    
            $plugin = [
                'id' => $remote['id'],
                'nom' => $remote['name'],
                'author' => $remote['author'] ?? 'Inconnu',
                'description' => $remote['description'] ?? '',
                'current_version' => $currentVer,
                'total_downloads' => $remote['total_downloads'] ?? 0,
                'repo_url' => $remote['repo_url'] ?? null,
                'licence' => $remote['licence'] ?? 'MIT',
                'status' => $status,
                'installed_version' => $installedVer,
                'update_available' => $updateAvailable,
                'actif' => $actif,
            ];
    
            return view('marketplace.show', compact('plugin'));           
        }catch(\Exception $e) {
            return redirect()->route('marketplace.index')->with('erreur', $e->getMessage());
        }
    }

    /**
     * Stream SSE pour l'installation d'un plugin.
     */
    public function streamInstall(string $id): StreamedResponse
    {
        return response()->stream(function () use ($id) {
            $sendEvent = function ($message, $progress, $status = 'progress') {
                echo 'data: '.json_encode(['message' => $message, 'progress' => $progress, 'status' => $status])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $this->service->installerPlugin($id, function ($message, $progress) use ($sendEvent) {
                    $sendEvent($message, $progress, 'progress');
                });
            } catch (\Throwable $e) {
                $sendEvent('Erreur : '.$e->getMessage(), 100, 'error');
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Stream SSE pour la mise à jour d'un plugin.
     */
    public function streamUpdate(string $id): StreamedResponse
    {
        // La mise à jour effectue la même séquence que l'installation
        return $this->streamInstall($id);
    }

    /**
     * Stream SSE pour la désinstallation d'un plugin.
     */
    public function streamUninstall(string $id): StreamedResponse
    {
        return response()->stream(function () use ($id) {
            $sendEvent = function ($message, $progress, $status = 'progress') {
                echo 'data: '.json_encode(['message' => $message, 'progress' => $progress, 'status' => $status])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $this->service->desinstallerPlugin($id, function ($message, $progress) use ($sendEvent) {
                    $sendEvent($message, $progress, 'progress');
                });
            } catch (\Throwable $e) {
                $sendEvent('Erreur : '.$e->getMessage(), 100, 'error');
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
