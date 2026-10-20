<?php

namespace App\View\Components;

use App\Plugins\PluginManager;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /** @var array{core: list<array<string, mixed>>, plugins: list<array<string, mixed>>} */
    public array $navigation;

    /** @var list<string> */
    public array $pluginStylesheets;

    /** @var list<string> */
    public array $pluginScripts;

    /** @var list<string> */
    public array $sidebarHooks;

    /** @var list<string> */
    public array $topbarActions;

    public function __construct(private readonly PluginManager $plugins)
    {
        $coreItems = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
            ['route' => 'sites.index', 'label' => 'Sites', 'icon' => 'globe'],
            ['route' => 'plugins.index', 'label' => 'Plugins', 'icon' => 'puzzle'],
            ['route' => 'marketplace.index', 'label' => 'Marketplace', 'icon' => 'shopping-cart'],
        ];

        $pluginItems = $this->plugins->navigation();
        $items = $this->plugins->filter('sidebar.items', [...$coreItems, ...$pluginItems], [
            'core' => $coreItems,
            'plugins' => $pluginItems,
        ]);

        if (! is_array($items)) {
            $items = [...$coreItems, ...$pluginItems];
        }

        $items = $this->validNavigationItems($items);

        $this->navigation = [
            'core' => array_values(array_filter(
                $items,
                static fn (array $item): bool => ! isset($item['plugin']),
            )),
            'plugins' => array_values(array_filter(
                $items,
                static fn (array $item): bool => isset($item['plugin']),
            )),
        ];

        $this->pluginStylesheets = $this->plugins->stylesheets();
        $this->pluginScripts = $this->plugins->scripts();
        $this->sidebarHooks = $this->plugins->renderHook('layout.sidebar.after', [
            'navigation' => $this->navigation,
        ]);
        $this->topbarActions = $this->plugins->renderHook('layout.topbar.actions', [
            'navigation' => $this->navigation,
        ]);
    }

    public function render(): View
    {
        return view('layouts.app');
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function validNavigationItems(array $items): array
    {
        return array_values(array_filter(
            $items,
            static fn (mixed $item): bool => is_array($item)
                && isset($item['route'], $item['label'])
                && is_string($item['route'])
                && is_string($item['label']),
        ));
    }
}
