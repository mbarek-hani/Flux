<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Plugins\PluginManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PluginController extends Controller
{
    public function __construct(private readonly PluginManager $manager) {}

    public function index(): View
    {
        $states = $this->manager->stateRepository()->all()->keyBy('slug');
        $plugins = [];

        foreach ($this->manager->discovered() as $slug => $discovered) {
            $state = $states->get($slug);
            $manifest = $discovered->manifest;

            $plugins[] = [
                'slug' => $slug,
                'name' => $manifest->name,
                'version' => $manifest->version,
                'description' => $manifest->description,
                'author' => $manifest->author,
                'active' => (bool) ($state?->is_active ?? false),
                'installed' => $state?->installed_at !== null,
                'tabs' => $manifest->tabs,
                'hooks' => array_map(fn ($hook) => $hook->name, $manifest->hooks),
            ];
        }

        return view('plugins.index', compact('plugins'));
    }

    public function show(string $slug): View
    {
        $manifest = $this->manager->manifest($slug);

        if ($manifest === null) {
            abort(404, 'Plugin not found');
        }

        $state = $this->manager->model($slug);
        $plugin = $this->manager->instance($slug);

        $details = [
            'slug' => $slug,
            'name' => $plugin?->name() ?? $manifest->name,
            'version' => $plugin?->version() ?? $manifest->version,
            'description' => $manifest->description !== '' ? $manifest->description : 'No description available.',
            'author' => $manifest->author,
            'license' => $manifest->license,
            'url' => $manifest->url,
            'active' => (bool) ($state?->is_active ?? false),
            'installed' => $state?->installed_at !== null,
            'enabled_at' => $state?->enabled_at,
            'tabs' => $manifest->tabs,
            'hooks' => array_map(fn ($hook) => $hook->name, $manifest->hooks),
            'navigation' => $manifest->navigation,
            'settings' => $manifest->settings,
        ];

        return view('plugins.show', compact('details'));
    }

    public function enable(string $slug): RedirectResponse
    {
        $success = $this->manager->enable($slug);

        return redirect()
            ->route('plugins.index')
            ->with($success ? 'success' : 'error', $success
                ? "Plugin [{$slug}] enabled."
                : 'Unable to enable the plugin.');
    }

    public function disable(string $slug): RedirectResponse
    {
        $success = $this->manager->disable($slug);

        return redirect()
            ->route('plugins.index')
            ->with($success ? 'success' : 'error', $success
                ? "Plugin [{$slug}] disabled."
                : 'Unable to disable the plugin.');
    }
}
