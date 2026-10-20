<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventsSeeder extends Seeder
{
    private array $types = [
        'click',
        'form',
        'download',
        'signup',
        'purchase',
        'share',
        'search',
        'video',
    ];

    private array $events = [
        'click' => [
            ['name' => 'Signup button click', 'data' => '{"button": "hero-cta"}'],
            ['name' => 'Navigation menu click', 'data' => '{"menu": "main"}'],
            ['name' => 'External link click', 'data' => '{"url": "https://github.com"}'],
            ['name' => 'Pricing button click', 'data' => '{"plan": "pro"}'],
        ],
        'form' => [
            ['name' => 'Contact form submitted', 'data' => '{"fields": 4}'],
            ['name' => 'Newsletter submitted', 'data' => '{"source": "footer"}'],
            ['name' => 'Search submitted', 'data' => '{"term": "analytics"}'],
        ],
        'download' => [
            ['name' => 'PDF download', 'data' => '{"file": "guide.pdf", "size": "2.4MB"}'],
            ['name' => 'Brochure download', 'data' => '{"file": "brochure.pdf", "size": "1.1MB"}'],
        ],
        'signup' => [
            ['name' => 'Free signup', 'data' => '{"plan": "free"}'],
            ['name' => 'Pro trial signup', 'data' => '{"plan": "pro", "duration": "14 days"}'],
        ],
        'purchase' => [
            ['name' => 'Monthly plan purchase', 'data' => '{"plan": "pro", "amount": 29.99}'],
            ['name' => 'Annual plan purchase', 'data' => '{"plan": "pro", "amount": 299.99}'],
            ['name' => 'Addon purchase', 'data' => '{"addon": "api-access", "amount": 9.99}'],
        ],
        'share' => [
            ['name' => 'Twitter share', 'data' => '{"network": "twitter"}'],
            ['name' => 'LinkedIn share', 'data' => '{"network": "linkedin"}'],
            ['name' => 'Facebook share', 'data' => '{"network": "facebook"}'],
        ],
        'search' => [
            ['name' => 'Internal search', 'data' => '{"term": "installation", "results": 5}'],
            ['name' => 'Internal search', 'data' => '{"term": "api", "results": 12}'],
            ['name' => 'Internal search', 'data' => '{"term": "pricing", "results": 3}'],
        ],
        'video' => [
            ['name' => 'Demo video played', 'data' => '{"video": "demo-v2", "duration": 120}'],
            ['name' => 'Tutorial video played', 'data' => '{"video": "install-tutorial", "duration": 300}'],
        ],
    ];

    private array $paths = [
        '/',
        '/about',
        '/contact',
        '/blog',
        '/blog/first-post',
        '/pricing',
        '/features',
        '/docs',
    ];

    public function run(): void
    {
        Site::query()->each(function (Site $site): void {
            $this->generateEvents($site);
        });
    }

    private function generateEvents(Site $site): void
    {
        $events = [];
        $eventCount = rand(80, 200);

        $visitIds = DB::table('visits')
            ->where('site_id', $site->id)
            ->pluck('id')
            ->all();

        for ($index = 0; $index < $eventCount; $index++) {
            $type = $this->types[array_rand($this->types)];
            $details = $this->events[$type][array_rand($this->events[$type])];
            $occurredAt = Carbon::now()
                ->subDays(rand(0, 30))
                ->subHours(rand(0, 23))
                ->subMinutes(rand(0, 59));

            $events[] = [
                'site_id' => $site->id,
                'visit_id' => $visitIds === [] ? null : $visitIds[array_rand($visitIds)],
                'session_id' => hash('sha256', Str::random(32)),
                'type' => $type,
                'name' => $details['name'],
                'data' => $details['data'],
                'path' => $this->paths[array_rand($this->paths)],
                'created_at' => $occurredAt,
            ];
        }

        foreach (array_chunk($events, 50) as $chunk) {
            DB::table('events')->insert($chunk);
        }
    }
}
