<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Analytics\Models\Event;
use App\Analytics\Models\Site;
use App\Analytics\Models\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_accepts_date_periods(): void
    {
        $site = Site::create([
            'name' => 'Stats Site',
            'domain' => 'stats.example.com',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/dashboard/stats?period=7d&site_id='.$site->id);

        $response->assertOk()
            ->assertJsonPath('summary.unique_visitors', 0)
            ->assertJsonStructure([
                'summary',
                'event_summary',
                'evolution',
                'top_pages',
                'events_by_name',
                'comparison' => [
                    'summary',
                    'evolution',
                    'period' => ['start', 'end'],
                ],
                'period' => ['start', 'end'],
            ]);
    }

    public function test_dashboard_stats_returns_accurate_event_summary_and_comparison(): void
    {
        $site = Site::create([
            'name' => 'Analytics Site',
            'domain' => 'analytics.example.com',
        ]);

        Visit::create([
            'site_id' => $site->id,
            'session_id' => 'session-a',
            'url' => 'https://analytics.example.com/',
            'path' => '/',
            'created_at' => now()->subDay(),
        ]);

        Visit::create([
            'site_id' => $site->id,
            'session_id' => 'session-b',
            'url' => 'https://analytics.example.com/pricing',
            'path' => '/pricing',
            'created_at' => now()->subDays(2),
        ]);

        Visit::create([
            'site_id' => $site->id,
            'session_id' => 'previous-session',
            'url' => 'https://analytics.example.com/',
            'path' => '/',
            'created_at' => now()->subDays(10),
        ]);

        Event::create([
            'site_id' => $site->id,
            'session_id' => 'session-a',
            'type' => 'custom',
            'name' => 'signup',
            'path' => '/',
            'created_at' => now()->subDay(),
        ]);

        Event::create([
            'site_id' => $site->id,
            'session_id' => 'session-a',
            'type' => 'custom',
            'name' => 'signup',
            'path' => '/',
            'created_at' => now()->subHours(12),
        ]);

        Event::create([
            'site_id' => $site->id,
            'session_id' => 'session-b',
            'type' => 'click',
            'name' => 'pricing_click',
            'path' => '/pricing',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/dashboard/stats?period=7d&site_id='.$site->id);

        $response->assertOk()
            ->assertJsonPath('event_summary.total_events', 3)
            ->assertJsonPath('event_summary.event_types', 2)
            ->assertJsonPath('event_summary.sessions_with_events', 2)
            ->assertJsonPath('event_summary.events_per_session', 1.5)
            ->assertJsonPath('comparison.summary.unique_visitors', 1)
            ->assertJsonPath('comparison.summary.page_views', 1);
    }
}
