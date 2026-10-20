<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Plugins\Models\Plugin as PluginModel;
use App\Plugins\PluginManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(PluginManager $manager): View
    {
        $settings = $manager->settings();
        $values = [];

        foreach (array_keys($settings) as $slug) {
            $values[$slug] = $manager->model($slug)?->configuration ?? [];
        }

        return view('settings.index', compact('settings', 'values'));
    }

    public function update(Request $request, PluginManager $manager): JsonResponse
    {
        $validated = $request->validate([
            'plugin' => ['required', 'string'],
            'settings' => ['required', 'array'],
        ]);

        $settings = $manager->settings();
        $schema = $settings[$validated['plugin']] ?? null;

        if ($schema === null) {
            return response()->json(['error' => 'Plugin not found.'], 404);
        }

        $fields = collect($schema['fields'])->keyBy('key');

        foreach ($fields as $key => $field) {
            if (($field['required'] ?? false) && blank($validated['settings'][$key] ?? null)) {
                return response()->json([
                    'error' => "The field [{$key}] is required.",
                ], 422);
            }
        }

        $plugin = PluginModel::query()->where('slug', $validated['plugin'])->first();

        if ($plugin === null) {
            return response()->json(['error' => 'Plugin not found.'], 404);
        }

        $configuration = array_merge(
            $plugin->configuration ?? [],
            Arr::only($validated['settings'], $fields->keys()->all()),
        );

        $plugin->update(['configuration' => $configuration]);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved.',
        ]);
    }
}
