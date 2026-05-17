<?php

namespace App\Http\Controllers;

use App\Models\Plugin;
use App\Services\PluginMarketplaceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceController extends Controller
{
    public function __construct(private PluginMarketplaceService $service) {}

    /**
     * Liste et recherche les plugins du Marketplace.
     */
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);
        $filter = $request->query('filter', 'all');

        $registryData = $this->service->getPlugins($search, $page);

        $remotePlugins = $registryData['data'] ?? [];
        $meta = $registryData['meta'] ?? [
            'total' => 0,
            'page' => 1,
            'page_size' => 20,
            'total_pages' => 1,
        ];

        // Enrichir avec l'état local
        $plugins = [];
        foreach ($remotePlugins as $remote) {
            $local = Plugin::where('identifiant', $remote['id'])->first();

            $status = 'not_installed';
            $installedVersion = null;
            $updateAvailable = false;
            $actif = false;

            if ($local && $local->installe) {
                $status = 'installed';
                $installedVersion = $local->version;
                $actif = $local->actif;

                // Si la version locale est différente de la version du registre, mise à jour disponible
                if ($local->version !== $remote['current_version']) {
                    $updateAvailable = true;
                }
            }

            $enriched = [
                'id' => $remote['id'],
                'nom' => $remote['name'],
                'author' => $remote['author'] ?? 'Inconnu',
                'description' => $remote['description'] ?? '',
                'current_version' => $remote['current_version'],
                'total_downloads' => $remote['total_downloads'] ?? 0,
                'repo_url' => $remote['repo_url'] ?? null,
                'status' => $status,
                'installed_version' => $installedVersion,
                'update_available' => $updateAvailable,
                'actif' => $actif,
            ];

            // Appliquer le filtre localement
            if ($filter === 'installed' && $status !== 'installed') {
                continue;
            }
            if ($filter === 'available' && $status !== 'not_installed') {
                continue;
            }

            $plugins[] = $enriched;
        }

        return view('marketplace.index', compact('plugins', 'search', 'page', 'filter', 'meta'));
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
