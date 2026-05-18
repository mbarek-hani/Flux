<?php

namespace App\Http\Controllers;

use App\Core\Plugin\PluginManager;
use App\Models\Plugin;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function __construct(private PluginManager $manager) {}

    public function index()
    {
        $decouverts = $this->manager->getPluginsDecouverts();

        // Construire la liste des plugins avec leur état
        $plugins = [];

        foreach ($decouverts as $id => $plugin) {
            $enBdd = Plugin::where('identifiant', $id)->first();

            $plugins[] = [
                'identifiant' => $id,
                'nom' => $plugin->getNom(),
                'version' => $plugin->getVersion(),
                'description' => $plugin->getManifest()['description'] ?? '',
                'auteur' => $plugin->getManifest()['auteur'] ?? 'Inconnu',
                'actif' => $enBdd?->actif ?? false,
                'installe' => $enBdd !== null && $enBdd->installe_le !== null,
                'onglets' => $plugin->getOnglets(),
                'hooks' => $plugin->getHooks(),
            ];
        }

        return view('plugins.index', compact('plugins'));
    }

    public function show(string $identifiant)
    {
        $decouverts = $this->manager->getPluginsDecouverts();

        if (! isset($decouverts[$identifiant])) {
            abort(404, 'Plugin non trouvé');
        }

        $plugin = $decouverts[$identifiant];
        $enBdd = Plugin::where('identifiant', $identifiant)->first();

        $details = [
            'identifiant' => $identifiant,
            'nom' => $plugin->getNom(),
            'version' => $plugin->getVersion(),
            'description' => $plugin->getManifest()['description'] ??
                'Aucune description disponible.',
            'auteur' => $plugin->getManifest()['auteur'] ?? 'Inconnu',
            'licence' => $plugin->getManifest()['licence'] ?? 'Non spécifiée',
            'url' => $plugin->getManifest()['url'] ?? null,
            'actif' => $enBdd?->actif ?? false,
            'installe' => $enBdd !== null && $enBdd->installe_le !== null,
            'active_le' => $enBdd?->active_le ?? null,
            'onglets' => $plugin->getOnglets(),
            'hooks' => $plugin->getHooks(),
            'navigation' => $plugin->getNavigationItems(),
            'reglages' => $plugin->getReglages(),
        ];

        return view('plugins.show', compact('details'));
    }

    public function activer(Request $request, string $identifiant)
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

    public function desactiver(Request $request, string $identifiant)
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
