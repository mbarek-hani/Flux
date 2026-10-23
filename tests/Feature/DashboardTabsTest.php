<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTabsTest extends TestCase
{
    public function test_dashboard_tab_overflow_is_rendered_only_with_extra_tabs(): void
    {
        $this->view('dashboard.partials.tabs', ['tabs' => []])
            ->assertDontSee('data-tab-overflow-trigger', false)
            ->assertDontSee('data-tab-overflow-panel', false);
    }

    public function test_dashboard_tab_overflow_contains_plugin_tabs(): void
    {
        $this->view('dashboard.partials.tabs', [
            'tabs' => [
                [
                    'id' => 'reports',
                    'label' => 'Reports',
                    'icon' => 'chart-bar',
                ],
                [
                    'id' => 'audit',
                    'label' => 'Audit log',
                    'icon' => 'document',
                ],
            ],
        ])
            ->assertSee('data-tab-overflow-panel', false)
            ->assertSee('data-tab-overflow-trigger', false)
            ->assertSee('id="dashboard-extra-tabs"', false)
            ->assertSee('aria-controls="dashboard-extra-tabs"', false)
            ->assertSee('data-tab-target="reports"', false)
            ->assertSee('data-tab-target="audit"', false)
            ->assertSee('Reports')
            ->assertSee('Audit log');
    }
}
