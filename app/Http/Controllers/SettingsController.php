<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Plugins\Models\Plugin as PluginModel;
use App\Plugins\PluginManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        $settings = $manager->settings();
        $schema = $settings[(string) $request->input('plugin')] ?? null;

        if ($schema === null) {
            return response()->json(['error' => 'Plugin not found.'], 404);
        }

        $rules = [
            'plugin' => ['required', 'string'],
            'settings' => ['required', 'array'],
        ];

        foreach ($schema['fields'] as $field) {
            $key = (string) $field['key'];
            $fieldRules = [($field['required'] ?? false) ? 'required' : 'nullable'];

            $fieldRules = [...$fieldRules, ...$this->rulesForType($field)];

            $rules['settings.'.$key] = $fieldRules;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $plugin = PluginModel::query()->where('slug', $validated['plugin'])->first();

        if ($plugin === null) {
            return response()->json(['error' => 'Plugin not found.'], 404);
        }

        $configuration = array_merge(
            $plugin->configuration ?? [],
            Arr::only($validated['settings'], array_column($schema['fields'], 'key')),
        );

        $plugin->update(['configuration' => $configuration]);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    private function rulesForType(array $field): array
    {
        return match ($field['type'] ?? 'text') {
            'email' => ['email'],
            'url' => ['url'],
            'number' => ['numeric'],
            'boolean' => ['boolean'],
            'select' => isset($field['options']) && $field['options'] !== []
                ? [Rule::in($field['options'])]
                : [],
            default => ['string'],
        };
    }
}
