<?php

declare(strict_types=1);

namespace App\Analytics\Services;

use App\Analytics\Models\Event;
use App\Analytics\Models\Site;
use App\Analytics\Models\Visit;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    public function __construct(private readonly Site $site) {}

    /**
     * @return array<string, int|float>
     */
    public function summary(DateTimeInterface|string $start, DateTimeInterface|string $end): array
    {
        $base = Visit::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end]);

        $visitors = (clone $base)->distinct('session_id')->count('session_id');
        $pageViews = (clone $base)->count();
        $bounces = (clone $base)->where('is_bounce', true)->count();
        $averageDuration = (clone $base)
            ->whereNotNull('session_duration')
            ->avg('session_duration');

        return [
            'unique_visitors' => $visitors,
            'page_views' => $pageViews,
            'new_sessions' => (clone $base)
                ->where('is_new_session', true)
                ->count(),
            'bounce_rate' => $visitors > 0 ? round(($bounces / $visitors) * 100, 1) : 0,
            'average_duration' => round($averageDuration ?? 0),
        ];
    }

    public function topPages(DateTimeInterface|string $start, DateTimeInterface|string $end, int $limit = 10): Collection
    {
        return Visit::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'path',
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
            )
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public function devices(DateTimeInterface|string $start, DateTimeInterface|string $end): Collection
    {
        return Visit::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'device',
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
            )
            ->groupBy('device')
            ->orderByDesc('visitors')
            ->get();
    }

    public function topCountries(DateTimeInterface|string $start, DateTimeInterface|string $end, int $limit = 10): Collection
    {
        return Visit::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('country_code')
            ->select(
                'country_code',
                'country_name',
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
            )
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get();
    }

    public function dailyEvolution(DateTimeInterface|string $start, DateTimeInterface|string $end): Collection
    {
        return Visit::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as page_views'),
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    public function topReferrers(DateTimeInterface|string $start, DateTimeInterface|string $end, int $limit = 10): Collection
    {
        return Visit::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('referrer_domain')
            ->select(
                'referrer_domain',
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
            )
            ->groupBy('referrer_domain')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get();
    }

    public function eventsByName(DateTimeInterface|string $start, DateTimeInterface|string $end, int $limit = 20): Collection
    {
        return Event::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'name',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT session_id) as sessions'),
            )
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function eventsByDay(DateTimeInterface|string $start, DateTimeInterface|string $end): Collection
    {
        return Event::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    public function latestEvents(DateTimeInterface|string $start, DateTimeInterface|string $end, int $limit = 20): Collection
    {
        return Event::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function eventsByPage(DateTimeInterface|string $start, DateTimeInterface|string $end): Collection
    {
        return $this->site
            ->events()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('path, count(*) as total')
            ->groupBy('path')
            ->orderByDesc('total')
            ->get();
    }

    public function topParameter(
        DateTimeInterface|string $start,
        DateTimeInterface|string $end,
        string $column,
        int $limit = 10,
    ): Collection {
        return Visit::query()
            ->where('site_id', $this->site->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull($column)
            ->select(
                $column,
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
            )
            ->groupBy($column)
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get();
    }
}
