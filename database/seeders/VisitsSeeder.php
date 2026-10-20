<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Analytics\Models\Site;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VisitsSeeder extends Seeder
{
    private array $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];

    private array $operatingSystems = ['Windows', 'macOS', 'Linux', 'Android', 'iOS'];

    private array $devices = ['desktop', 'mobile', 'tablet', 'unknown'];

    private array $countries = [
        ['code' => 'FR', 'name' => 'France'],
        ['code' => 'US', 'name' => 'United States'],
        ['code' => 'MA', 'name' => 'Morocco'],
        ['code' => 'DE', 'name' => 'Germany'],
        ['code' => 'GB', 'name' => 'United Kingdom'],
        ['code' => 'CA', 'name' => 'Canada'],
        ['code' => 'ES', 'name' => 'Spain'],
        ['code' => 'JP', 'name' => 'Japan'],
    ];

    private array $paths = [
        '/',
        '/about',
        '/contact',
        '/blog',
        '/blog/first-post',
        '/blog/laravel-tips',
        '/blog/analytics-guide',
        '/pricing',
        '/features',
        '/docs',
        '/docs/installation',
        '/docs/api',
    ];

    private array $titles = [
        '/' => 'Home',
        '/about' => 'About',
        '/contact' => 'Contact',
        '/blog' => 'Blog',
        '/blog/first-post' => 'My first post',
        '/blog/laravel-tips' => 'Laravel tips',
        '/blog/analytics-guide' => 'Analytics guide',
        '/pricing' => 'Pricing',
        '/features' => 'Features',
        '/docs' => 'Documentation',
        '/docs/installation' => 'Installation',
        '/docs/api' => 'API Reference',
    ];

    private array $referrers = [
        null,
        'https://google.com',
        'https://google.fr',
        'https://www.facebook.com',
        'https://twitter.com',
        'https://www.linkedin.com',
        'https://www.reddit.com',
        'https://github.com',
        'https://www.youtube.com',
    ];

    private array $referrerDomains = [
        null,
        'google.com',
        'google.fr',
        'facebook.com',
        'twitter.com',
        'linkedin.com',
        'reddit.com',
        'github.com',
        'youtube.com',
    ];

    private array $utmSources = [null, 'google', 'facebook', 'twitter', 'newsletter', 'linkedin'];

    private array $utmMediums = [null, 'cpc', 'social', 'email', 'organic', 'referral'];

    private array $utmCampaigns = [null, 'v2-launch', 'summer-promo', 'black-friday', 'june-newsletter'];

    public function run(): void
    {
        Site::query()->each(function (Site $site): void {
            $this->generateVisits($site);
        });
    }

    private function generateVisits(Site $site): void
    {
        $visits = [];
        $visitCount = rand(150, 300);

        for ($index = 0; $index < $visitCount; $index++) {
            $sessionId = hash('sha256', Str::random(32));
            $path = $this->paths[array_rand($this->paths)];
            $country = $this->countries[array_rand($this->countries)];
            $referrerIndex = array_rand($this->referrers);
            $visitedAt = Carbon::now()
                ->subDays(rand(0, 30))
                ->subHours(rand(0, 23))
                ->subMinutes(rand(0, 59));

            $visits[] = [
                'site_id' => $site->id,
                'session_id' => $sessionId,
                'url' => 'https://'.$site->domain.$path,
                'path' => $path,
                'title' => $this->titles[$path] ?? null,
                'referrer' => $this->referrers[$referrerIndex],
                'referrer_domain' => $this->referrerDomains[$referrerIndex],
                'utm_source' => $this->utmSources[array_rand($this->utmSources)],
                'utm_medium' => $this->utmMediums[array_rand($this->utmMediums)],
                'utm_campaign' => $this->utmCampaigns[array_rand($this->utmCampaigns)],
                'browser' => $this->browsers[array_rand($this->browsers)],
                'browser_version' => (string) rand(90, 130),
                'operating_system' => $this->operatingSystems[array_rand($this->operatingSystems)],
                'device' => $this->devices[array_rand($this->devices)],
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'session_duration' => rand(5, 600),
                'is_bounce' => (bool) rand(0, 1),
                'is_new_session' => (bool) rand(0, 1),
                'created_at' => $visitedAt,
            ];
        }

        foreach (array_chunk($visits, 50) as $chunk) {
            DB::table('visits')->insert($chunk);
        }
    }
}
