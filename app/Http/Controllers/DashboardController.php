<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Analytics\Models\Site;
use App\Analytics\Services\StatisticsService;
use App\Plugins\PluginManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(PluginManager $plugins): View
    {
        $sites = Site::query()->active()->latest()->get();
        $currentSite = $sites->first();

        if ($currentSite === null) {
            return view('dashboard', ['noSite' => true]);
        }

        return view('dashboard', [
            'sites' => $sites,
            'currentSite' => $currentSite,
            'noSite' => false,
            'tabs' => $plugins->tabs(),
            'settings' => $plugins->settings(),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'period' => [
                'required',
                'in:today,7d,30d,this_month,this_year',
            ],
        ]);

        $site = Site::findOrFail($request->integer('site_id'));
        [$start, $end] = $this->calculatePeriod($request->string('period')->toString());
        $statistics = new StatisticsService($site);

        return response()->json([
            'summary' => $statistics->summary($start, $end),
            'evolution' => $statistics->dailyEvolution($start, $end),
            'top_pages' => $statistics->topPages($start, $end),
            'top_referrers' => $statistics->topReferrers($start, $end),
            'top_countries' => $statistics->topCountries($start, $end),
            'devices' => $statistics->devices($start, $end),
            'events_by_name' => $statistics->eventsByName($start, $end),
            'events_by_day' => $statistics->eventsByDay($start, $end),
            'events_by_page' => $statistics->eventsByPage($start, $end),
            'latest_events' => $statistics->latestEvents($start, $end),
            'top_utm_sources' => $statistics->topParameter($start, $end, 'utm_source'),
            'top_utm_mediums' => $statistics->topParameter($start, $end, 'utm_medium'),
            'top_utm_campaigns' => $statistics->topParameter($start, $end, 'utm_campaign'),
            'top_browsers' => $statistics->topParameter($start, $end, 'browser'),
            'top_browser_versions' => $statistics->topParameter($start, $end, 'browser_version'),
            'top_operating_systems' => $statistics->topParameter($start, $end, 'operating_system'),
            'period' => [
                'start' => $start->format('d/m/Y'),
                'end' => $end->format('d/m/Y'),
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function calculatePeriod(string $period): array
    {
        return match ($period) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            '30d' => [Carbon::now()->subDays(30), Carbon::now()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()],
            default => [Carbon::now()->subDays(7), Carbon::now()],
        };
    }
}
