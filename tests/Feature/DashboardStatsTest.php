<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Analytics\Models\Site;
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
                'evolution',
                'top_pages',
                'events_by_name',
                'period' => ['start', 'end'],
            ]);
    }
}
