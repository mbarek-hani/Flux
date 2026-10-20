<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Analytics\Events\EventRecorded;
use App\Analytics\Events\VisitRecorded;
use App\Analytics\Models\Site;
use App\Analytics\Services\TrackingService;
use App\Events\PluginDisabled;
use App\Events\PluginEnabled;
use App\Plugins\AbstractPlugin;
use App\Plugins\Contracts\Plugin;
use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TrackingPipelineTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'tracking_token' => str_repeat('a', 64),
            'is_active' => true,
        ]);
    }

    public function test_plugin_can_enrich_tracking_data(): void
    {
        $plugin = new class extends AbstractPlugin
        {
            public function slug(): string
            {
                return 'enricher-plugin';
            }

            public function name(): string
            {
                return 'Enricher Plugin';
            }

            public function version(): string
            {
                return '1.0.0';
            }

            public function filterVisit(array $data, Request $request): ?array
            {
                $data['title'] = 'Enriched Title: '.($data['title'] ?? '');
                $data['utm_source'] = 'plugin-source';

                return $data;
            }
        };

        $this->injectActivePlugins(['enricher-plugin' => $plugin]);

        $visit = app(TrackingService::class)->recordVisit($this->site, [
            'url' => 'https://example.com/test',
            'path' => '/test',
            'title' => 'Original Title',
        ], $this->request());

        $this->assertNotNull($visit);
        $this->assertSame('Enriched Title: Original Title', $visit->title);
        $this->assertSame('plugin-source', $visit->utm_source);
    }

    public function test_plugin_can_cancel_visit_tracking_by_returning_null(): void
    {
        $plugin = new class extends AbstractPlugin
        {
            public function slug(): string
            {
                return 'filter-plugin';
            }

            public function name(): string
            {
                return 'Filter Plugin';
            }

            public function version(): string
            {
                return '1.0.0';
            }

            public function filterVisit(array $data, Request $request): ?array
            {
                return $request->ip() === '127.0.0.1' ? null : $data;
            }
        };

        $this->injectActivePlugins(['filter-plugin' => $plugin]);

        $service = app(TrackingService::class);

        $cancelled = $service->recordVisit($this->site, [
            'url' => 'https://example.com/home',
            'path' => '/home',
        ], $this->request('127.0.0.1'));

        $this->assertNull($cancelled);
        $this->assertDatabaseCount('visits', 0);

        $recorded = $service->recordVisit($this->site, [
            'url' => 'https://example.com/home',
            'path' => '/home',
        ], $this->request('8.8.8.8'));

        $this->assertNotNull($recorded);
        $this->assertDatabaseCount('visits', 1);
    }

    public function test_plugin_can_cancel_event_tracking_by_returning_null(): void
    {
        $plugin = new class extends AbstractPlugin
        {
            public function slug(): string
            {
                return 'event-filter-plugin';
            }

            public function name(): string
            {
                return 'Event Filter Plugin';
            }

            public function version(): string
            {
                return '1.0.0';
            }

            public function filterEvent(array $data, Request $request): ?array
            {
                return ($data['name'] ?? '') === 'blocked_event' ? null : $data;
            }
        };

        $this->injectActivePlugins(['event-filter-plugin' => $plugin]);

        $service = app(TrackingService::class);

        $blocked = $service->recordEvent($this->site, ['name' => 'blocked_event'], $this->request());

        $this->assertNull($blocked);
        $this->assertDatabaseCount('events', 0);

        $allowed = $service->recordEvent($this->site, ['name' => 'allowed_event'], $this->request());

        $this->assertNotNull($allowed);
        $this->assertDatabaseCount('events', 1);
    }

    public function test_visit_and_event_events_are_dispatched(): void
    {
        Event::fake([VisitRecorded::class, EventRecorded::class]);

        $service = app(TrackingService::class);
        $request = $this->request('1.2.3.4', 'https://google.com');

        $visit = $service->recordVisit($this->site, [
            'url' => 'https://example.com/page',
            'path' => '/page',
        ], $request);

        Event::assertDispatched(VisitRecorded::class, function (VisitRecorded $event) use ($visit): bool {
            return $event->visit->id === $visit->id
                && $event->metadata['ip'] === '1.2.3.4'
                && $event->metadata['referer'] === 'https://google.com';
        });

        $service->recordEvent($this->site, ['name' => 'button_click'], $request);

        Event::assertDispatched(EventRecorded::class, function (EventRecorded $event): bool {
            return $event->event->name === 'button_click'
                && $event->metadata['ip'] === '1.2.3.4';
        });
    }

    public function test_plugin_event_listeners_are_registered(): void
    {
        $plugin = new class extends AbstractPlugin
        {
            public static ?int $lastVisitId = null;

            public function slug(): string
            {
                return 'listener-plugin';
            }

            public function name(): string
            {
                return 'Listener Plugin';
            }

            public function version(): string
            {
                return '1.0.0';
            }

            public function listeners(): array
            {
                return [
                    VisitRecorded::class => [
                        function (VisitRecorded $event): void {
                            self::$lastVisitId = $event->visit->id;
                        },
                    ],
                ];
            }
        };

        $plugin->register();

        $visit = app(TrackingService::class)->recordVisit($this->site, [
            'url' => 'https://example.com/blog',
            'path' => '/blog',
        ], $this->request());

        $this->assertNotNull($visit);
        $this->assertSame($visit->id, $plugin::$lastVisitId);
    }

    public function test_plugin_enabled_and_disabled_events_are_dispatched(): void
    {
        Event::fake([PluginEnabled::class, PluginDisabled::class]);

        $pluginDir = base_path('plugins/EventTestPlugin');
        File::ensureDirectoryExists($pluginDir.'/src');

        File::put($pluginDir.'/manifest.json', json_encode([
            'slug' => 'event-test-plugin',
            'name' => 'Event Test Plugin',
            'version' => '1.0.0',
            'class' => 'Plugins\\EventTestPlugin\\EventTestPlugin',
        ]));

        File::put($pluginDir.'/src/EventTestPlugin.php', <<<'PHP'
<?php

namespace Plugins\EventTestPlugin;

use App\Plugins\AbstractPlugin;

class EventTestPlugin extends AbstractPlugin
{
    public function slug(): string { return 'event-test-plugin'; }
    public function name(): string { return 'Event Test Plugin'; }
    public function version(): string { return '1.0.0'; }
}
PHP);

        try {
            $manager = app(PluginManager::class);
            $manager->reset();

            $this->assertTrue($manager->enable('event-test-plugin'));
            Event::assertDispatched(PluginEnabled::class, fn (PluginEnabled $event): bool => $event->slug === 'event-test-plugin');

            $this->assertTrue($manager->disable('event-test-plugin'));
            Event::assertDispatched(PluginDisabled::class, fn (PluginDisabled $event): bool => $event->slug === 'event-test-plugin');
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    private function request(string $ip = '8.8.8.8', ?string $referer = null): Request
    {
        $server = [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64)',
        ];

        if ($referer !== null) {
            $server['HTTP_REFERER'] = $referer;
        }

        return Request::create('/tracker.js', 'POST', [], [], [], $server);
    }

    /**
     * @param  array<string, Plugin>  $plugins
     */
    private function injectActivePlugins(array $plugins): void
    {
        $manager = app(PluginManager::class);
        $reflection = new \ReflectionClass($manager);
        $property = $reflection->getProperty('active');
        $property->setAccessible(true);
        $property->setValue($manager, $plugins);
    }
}
