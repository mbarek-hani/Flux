<?php

namespace App\Http\Controllers;

use App\Core\Plugin\PluginManager;
use App\Models\Plugin;
use App\Services\MarketplaceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceController extends Controller
{
    public function __construct(private MarketplaceService $service, private PluginManager $manager) {}

    /**
     * Liste et recherche les plugins du Marketplace.
     */
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $page = $request->integer('page', 1);

        try {
            $registryData = $this->service->getPlugins($search, $page);

            $remotePlugins = $registryData['data'] ?? [];
            $meta = $registryData['meta'] ?? $this->service->defaultMeta();
        } catch (\Throwable $e) {
            session()->flash(
                'erreur',
                $e->getMessage(),
            );

            return view('marketplace.index', [
                'plugins' => [],
                'search' => $search,
                'page' => $page,
                'meta' => $this->service->defaultMeta(),
            ]);
        }

        $localPlugins = Plugin::query()
            ->whereIn('identifiant', collect($remotePlugins)->pluck('id'))
            ->get()
            ->keyBy('identifiant');

        $plugins = collect($remotePlugins)
            ->map(fn ($remote) => $this->service->enrichPlugin(
                $remote,
                $localPlugins->get($remote['id'])
            ))
            ->values();

        return view('marketplace.index', [
            'plugins' => $plugins,
            'search' => $search,
            'page' => $page,
            'meta' => $meta,
        ]);
    }

    /**
     * Affiche les détails d'un plugin du Marketplace.
     */
    public function show(string $id)
    {
        try {
            $remote = $this->service->getPluginDetails($id);

            $local = Plugin::where('identifiant', $remote['id'])->first();

            $status = 'not_installed';
            $installedVersion = null;
            $updateAvailable = false;
            $actif = false;

            if ($local && $local->installe_le !== null) {
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
            if (! str_starts_with(strtolower($currentVer), 'v')) {
                $currentVer = 'v'.$currentVer;
            }
            $installedVer = $installedVersion;
            if ($installedVer && ! str_starts_with(strtolower($installedVer), 'v')) {
                $installedVer = 'v'.$installedVer;
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
        } catch (\Exception $e) {
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
                $this->service->installerPlugin($this->manager, $id, function ($message, $progress) use ($sendEvent) {
                    $sendEvent($message, $progress, 'progress');
                });

                // Envoyer l'événement de succès final pour que le client sache que c'est terminé
                $sendEvent('Installation terminée avec succès !', 100, 'success');
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
        return response()->stream(function () use ($id) {
            $sendEvent = function ($message, $progress, $status = 'progress') {
                echo 'data: '.json_encode(['message' => $message, 'progress' => $progress, 'status' => $status])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $this->service->installerPlugin($this->manager, $id, function ($message, $progress) use ($sendEvent) {
                    $sendEvent($message, $progress, 'progress');
                });

                // Envoyer l'événement de succès final pour que le client sache que c'est terminé
                $sendEvent('Mise à jour terminée avec succès !', 100, 'success');
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
                $this->service->desinstallerPlugin($this->manager, $id, function ($message, $progress) use ($sendEvent) {
                    $sendEvent($message, $progress, 'progress');
                });

                // Envoyer l'événement de succès final pour que le client sache que c'est terminé
                $sendEvent('Désinstallation terminée avec succès !', 100, 'success');
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
