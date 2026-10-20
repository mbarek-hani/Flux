<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Plugin\PluginManager;
use App\Models\Plugin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PluginController extends Controller
{
    public function __construct(private readonly PluginManager $manager) {}

    public function index(): View
    {
        $decouverts = $this->manager->getPluginsDecouverts();
        $ids = array_keys($decouverts);

        // Optimisation N+1 : charger tous les modèles en une seule requête
        $enBdd = Plugin::whereIn('identifiant', $ids)->get()->keyBy('identifiant');

        $plugins = [];

        foreach ($decouverts as $id => $metadata) {
            $model = $enBdd->get($id);

            $plugins[] = [
                'identifiant' => $id,
                'nom' => $metadata->getNom(),
                'version' => $metadata->getVersion(),
                'description' => $metadata->getDescription(),
                'auteur' => $metadata->getAuteur(),
                'actif' => $model?->actif ?? false,
                'installe' => $model !== null && $model->installe_le !== null,
                'onglets' => $metadata->getOnglets(),
                'hooks' => $metadata->getHooks(),
            ];
        }

        return view('plugins.index', compact('plugins'));
    }

    public function show(string $identifiant): View
    {
        $metadata = $this->manager->getPluginMetadata($identifiant);

        if (! $metadata) {
            abort(404, 'Plugin non trouvé');
        }

        $enBdd = Plugin::where('identifiant', $identifiant)->first();
        $plugin = $this->manager->getPluginInstance($identifiant);

        $details = [
            'identifiant' => $identifiant,
            'nom' => $plugin?->getNom() ?? $metadata->getNom(),
            'version' => $plugin?->getVersion() ?? $metadata->getVersion(),
            'description' => $metadata->getDescription() !== '' ? $metadata->getDescription() : 'Aucune description disponible.',
            'auteur' => $metadata->getAuteur(),
            'licence' => $metadata->getLicence(),
            'url' => $metadata->getUrl(),
            'actif' => $enBdd?->actif ?? false,
            'installe' => $enBdd !== null && $enBdd->installe_le !== null,
            'active_le' => $enBdd?->active_le ?? null,
            'onglets' => $plugin?->getOnglets() ?? $metadata->getOnglets(),
            'hooks' => $plugin?->getHooks() ?? $metadata->getHooks(),
            'navigation' => $plugin?->getNavigationItems() ?? $metadata->getNavigationItems(),
            'reglages' => $plugin?->getReglages() ?? $metadata->getReglages(),
        ];

        return view('plugins.show', compact('details'));
    }

    public function activer(Request $request, string $identifiant): RedirectResponse
    {
        $succes = $this->manager->activer($identifiant);

        return redirect()
            ->route('plugins.index')
            ->with(
                $succes ? 'succes' : 'erreur',
                $succes
                    ? "Plugin « {$identifiant} » activé."
                    : "Impossible d'activer le plugin.",
            );
    }

    public function desactiver(Request $request, string $identifiant): RedirectResponse
    {
        $succes = $this->manager->desactiver($identifiant);

        return redirect()
            ->route('plugins.index')
            ->with(
                $succes ? 'succes' : 'erreur',
                $succes
                    ? "Plugin « {$identifiant} » désactivé."
                    : 'Impossible de désactiver le plugin.',
            );
    }
}
