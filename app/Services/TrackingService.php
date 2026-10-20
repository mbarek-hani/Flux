<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\EventRecorded;
use App\Events\VisitRecorded;
use App\Models\Event;
use App\Models\Site;
use App\Models\Visit;
use App\Plugins\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class TrackingService
{
    private const SESSION_DURATION_SECONDS = 1800;

    public function __construct(private readonly PluginManager $plugins) {}

    public function recordVisit(Site $site, array $data, Request $request): ?Visit
    {
        try {
            if ($site->ignore_bots && $this->isBot($request->userAgent())) {
                return null;
            }

            $data = $this->plugins->filterVisit($data, $request);

            if ($data === null) {
                return null;
            }

            $sessionId = $this->generateSessionId($request, $site->id);
            $browser = $this->parseUserAgent($request->userAgent());
            $country = $this->detectCountry($request);

            $visit = Visit::create([
                'site_id' => $site->id,
                'session_id' => $sessionId,
                'url' => $this->cleanUrl($data['url'] ?? ''),
                'path' => $this->cleanPath($data['path'] ?? '/'),
                'title' => $data['title'] ?? null,
                'referrer' => $data['referrer'] ?? null,
                'referrer_domain' => $data['referrer_domain'] ?? null,
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'browser' => $browser['browser'],
                'browser_version' => $browser['version'],
                'operating_system' => $browser['operating_system'],
                'device' => $data['device'] ?? $browser['device'],
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'is_bounce' => true,
                'is_new_session' => $this->isNewSession($sessionId),
                'created_at' => now(),
            ]);

            VisitRecorded::dispatch($visit, $data, $this->requestMetadata($request, $site));

            return $visit;
        } catch (\Throwable $exception) {
            Log::error('TrackingService::recordVisit: '.$exception->getMessage());

            return null;
        }
    }

    public function updateDuration(Site $site, array $data, Request $request): void
    {
        try {
            $sessionId = $this->generateSessionId($request, $site->id);
            $duration = (int) ($data['duration'] ?? 0);

            $visit = Visit::query()
                ->where('site_id', $site->id)
                ->where('session_id', $sessionId)
                ->where('path', $data['path'] ?? '/')
                ->latest('created_at')
                ->first();

            if ($visit === null) {
                return;
            }

            $visit->update([
                'session_duration' => $duration,
                'is_bounce' => $duration < 10,
            ]);
        } catch (\Throwable $exception) {
            Log::error('TrackingService::updateDuration: '.$exception->getMessage());
        }
    }

    public function recordEvent(Site $site, array $data, Request $request): ?Event
    {
        try {
            $sessionId = $this->generateSessionId($request, $site->id);
            $data = $this->plugins->filterEvent($data, $request);

            if ($data === null) {
                return null;
            }

            $visit = Visit::query()
                ->where('site_id', $site->id)
                ->where('session_id', $sessionId)
                ->latest('created_at')
                ->first();

            $event = Event::create([
                'site_id' => $site->id,
                'visit_id' => $visit?->id,
                'session_id' => $sessionId,
                'type' => 'custom',
                'name' => $data['name'] ?? 'Event',
                'data' => $data['data'] ?? null,
                'path' => $data['path'] ?? null,
                'created_at' => now(),
            ]);

            EventRecorded::dispatch($event, $data, $this->requestMetadata($request, $site));

            return $event;
        } catch (\Throwable $exception) {
            Log::error('TrackingService::recordEvent: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * @return array{code: ?string, name: ?string}
     */
    private function detectCountry(Request $request): array
    {
        try {
            $location = Location::get($request->ip());

            if ($location && $location->countryCode) {
                return [
                    'code' => strtoupper($location->countryCode),
                    'name' => $location->countryName ?? $location->countryCode,
                ];
            }
        } catch (\Throwable $exception) {
            Log::warning('Geolocation failed: '.$exception->getMessage());
        }

        return ['code' => null, 'name' => null];
    }

    private function generateSessionId(Request $request, int $siteId): string
    {
        $ip = $request->ip() ?? '';
        $userAgent = $request->userAgent() ?? '';
        $date = now()->format('Y-m-d');
        $salt = (string) config('app.key');

        return hash('sha256', $ip.$userAgent.$date.$siteId.$salt);
    }

    private function isNewSession(string $sessionId): bool
    {
        return ! Visit::query()
            ->where('session_id', $sessionId)
            ->where('created_at', '>=', now()->subSeconds(self::SESSION_DURATION_SECONDS))
            ->exists();
    }

    /**
     * @return array{browser: ?string, version: ?string, operating_system: ?string, device: string}
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (! $userAgent) {
            return [
                'browser' => null,
                'version' => null,
                'operating_system' => null,
                'device' => 'unknown',
            ];
        }

        $browser = 'Other';
        $version = null;

        $browsers = [
            'Edg' => 'Edge',
            'OPR' => 'Opera',
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
        ];

        foreach ($browsers as $needle => $name) {
            if (str_contains($userAgent, $needle)) {
                $browser = $name;
                preg_match("/{$needle}\/([0-9]+)/", $userAgent, $matches);
                $version = $matches[1] ?? null;
                break;
            }
        }

        $operatingSystem = 'Other';

        $operatingSystems = [
            'Windows NT 10' => 'Windows 10/11',
            'Windows NT 6' => 'Windows',
            'Mac OS X' => 'macOS',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iPadOS',
        ];

        foreach ($operatingSystems as $needle => $name) {
            if (str_contains($userAgent, $needle)) {
                $operatingSystem = $name;
                break;
            }
        }

        $device = 'desktop';

        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            $device = 'tablet';
        } elseif (preg_match('/mobile|iphone|ipod|android|blackberry/i', $userAgent)) {
            $device = 'mobile';
        }

        return [
            'browser' => $browser,
            'version' => $version,
            'operating_system' => $operatingSystem,
            'device' => $device,
        ];
    }

    private function isBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return true;
        }

        $pattern = '/bot|crawl|spider|slurp|teoma|archive|track|'.
            'facebookexternalhit|Twitterbot|LinkedInBot|'.
            'WhatsApp|Googlebot|bingbot|yandex/i';

        return (bool) preg_match($pattern, $userAgent);
    }

    private function cleanUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        try {
            $parts = parse_url($url);
            $cleaned = ($parts['scheme'] ?? 'https').
                '://'.
                ($parts['host'] ?? '').
                ($parts['path'] ?? '/');

            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);

                $utmParameters = array_filter(
                    $query,
                    fn (string $key): bool => str_starts_with($key, 'utm_'),
                    ARRAY_FILTER_USE_KEY,
                );

                if (! empty($utmParameters)) {
                    $cleaned .= '?'.http_build_query($utmParameters);
                }
            }

            return substr($cleaned, 0, 2048);
        } catch (\Throwable) {
            return substr($url, 0, 2048);
        }
    }

    private function cleanPath(string $path): string
    {
        $path = rtrim($path, '/') ?: '/';

        return substr($path, 0, 1024);
    }

    /**
     * @return array{ip: ?string, user_agent: ?string, referer: ?string, site_id: int}
     */
    private function requestMetadata(Request $request, Site $site): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'site_id' => $site->id,
        ];
    }
}
