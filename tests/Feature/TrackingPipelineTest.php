<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Plugin\AbstractPlugin;
use App\Core\Plugin\PluginManager;
use App\Events\EvenementEnregistre;
use App\Events\PluginActive;
use App\Events\PluginDesactive;
use App\Events\VisiteEnregistree;
use App\Models\Site;
use App\Models\User;
use App\Services\TrackingService;
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

        $user = User::factory()->create();
        $this->site = Site::create([
            'user_id' => $user->id,
            'nom' => 'Test Site',
            'domaine' => 'example.com',
            'token_tracking' => str_repeat('a', 64),
            'actif' => true,
        ]);
    }

    public function test_plugin_can_enrich_tracking_data(): void
    {
        $plugin = new class extends AbstractPlugin {
            public function getIdentifiant(): string { return 'enricher-plugin'; }
            public function getNom(): string { return 'Enricher Plugin'; }
            public function getVersion(): string { return '1.0.0'; }

            public function enrichirTracking(array $donnees, $request): ?array
            {
                $donnees['titre'] = 'Enriched Title: '.$donnees['titre'];
                $donnees['utm_source'] = 'plugin-source';
                return $donnees;
            }
        };

        $manager = app(PluginManager::class);
        $reflection = new \ReflectionClass($manager);
        $actifsProp = $reflection->getProperty('pluginsActifs');
        $actifsProp->setAccessible(true);
        $actifsProp->setValue($manager, ['enricher-plugin' => $plugin]);

        $service = app(TrackingService::class);
        $request = Request::create('/tracker.js', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64)',
        ]);

        $visite = $service->enregistrerVisite($this->site, [
            'url' => 'https://example.com/test',
            'chemin' => '/test',
            'titre' => 'Original Title',
        ], $request);

        $this->assertNotNull($visite);
        $this->assertSame('Enriched Title: Original Title', $visite->titre);
        $this->assertSame('plugin-source', $visite->utm_source);
    }

    public function test_plugin_can_cancel_visit_tracking_by_returning_null(): void
    {
        $plugin = new class extends AbstractPlugin {
            public function getIdentifiant(): string { return 'filter-plugin'; }
            public function getNom(): string { return 'Filter Plugin'; }
            public function getVersion(): string { return '1.0.0'; }

            public function enrichirTracking(array $donnees, $request): ?array
            {
                if ($request->ip() === '127.0.0.1') {
                    return null; // Cancel tracking
                }
                return $donnees;
            }
        };

        $manager = app(PluginManager::class);
        $reflection = new \ReflectionClass($manager);
        $actifsProp = $reflection->getProperty('pluginsActifs');
        $actifsProp->setAccessible(true);
        $actifsProp->setValue($manager, ['filter-plugin' => $plugin]);

        $service = app(TrackingService::class);

        // 1. Filtered request (127.0.0.1) -> should be cancelled
        $filteredRequest = Request::create('/tracker.js', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $visite = $service->enregistrerVisite($this->site, [
            'url' => 'https://example.com/home',
            'chemin' => '/home',
        ], $filteredRequest);

        $this->assertNull($visite);
        $this->assertDatabaseCount('visites', 0);

        // 2. Allowed request (other IP) -> should be recorded
        $allowedRequest = Request::create('/tracker.js', 'POST', [], [], [], [
            'REMOTE_ADDR' => '8.8.8.8',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $visite2 = $service->enregistrerVisite($this->site, [
            'url' => 'https://example.com/home',
            'chemin' => '/home',
        ], $allowedRequest);

        $this->assertNotNull($visite2);
        $this->assertDatabaseCount('visites', 1);
    }

    public function test_plugin_can_cancel_event_tracking_by_returning_null(): void
    {
        $plugin = new class extends AbstractPlugin {
            public function getIdentifiant(): string { return 'event-filter-plugin'; }
            public function getNom(): string { return 'Event Filter Plugin'; }
            public function getVersion(): string { return '1.0.0'; }

            public function enrichirTracking(array $donnees, $request): ?array
            {
                if (($donnees['nom'] ?? '') === 'blocked_event') {
                    return null;
                }
                return $donnees;
            }
        };

        $manager = app(PluginManager::class);
        $reflection = new \ReflectionClass($manager);
        $actifsProp = $reflection->getProperty('pluginsActifs');
        $actifsProp->setAccessible(true);
        $actifsProp->setValue($manager, ['event-filter-plugin' => $plugin]);

        $service = app(TrackingService::class);
        $request = Request::create('/tracker.js', 'POST');

        // Blocked event
        $evt1 = $service->enregistrerEvenement($this->site, [
            'nom' => 'blocked_event',
        ], $request);

        $this->assertNull($evt1);
        $this->assertDatabaseCount('evenements', 0);

        // Allowed event
        $evt2 = $service->enregistrerEvenement($this->site, [
            'nom' => 'allowed_event',
        ], $request);

        $this->assertNotNull($evt2);
        $this->assertDatabaseCount('evenements', 1);
    }

    public function test_visite_and_evenement_events_are_dispatched(): void
    {
        Event::fake([
            VisiteEnregistree::class,
            EvenementEnregistre::class,
        ]);

        $service = app(TrackingService::class);
        $request = Request::create('/tracker.js', 'POST', [], [], [], [
            'REMOTE_ADDR' => '1.2.3.4',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_REFERER' => 'https://google.com',
        ]);

        $visite = $service->enregistrerVisite($this->site, [
            'url' => 'https://example.com/page',
            'chemin' => '/page',
        ], $request);

        Event::assertDispatched(VisiteEnregistree::class, function (VisiteEnregistree $event) use ($visite) {
            return $event->visite->id === $visite->id &&
                $event->metadonnees['ip'] === '1.2.3.4' &&
                $event->metadonnees['referer'] === 'https://google.com';
        });

        $service->enregistrerEvenement($this->site, [
            'nom' => 'button_click',
        ], $request);

        Event::assertDispatched(EvenementEnregistre::class, function (EvenementEnregistre $event) {
            return $event->evenement->nom === 'button_click' &&
                $event->metadonnees['ip'] === '1.2.3.4';
        });
    }

    public function test_plugin_event_listeners_registered_via_get_ecouteurs(): void
    {
        $handledVisiteId = null;

        $plugin = new class($handledVisiteId) extends AbstractPlugin {
            public static $lastVisiteId = null;

            public function getIdentifiant(): string { return 'listener-plugin'; }
            public function getNom(): string { return 'Listener Plugin'; }
            public function getVersion(): string { return '1.0.0'; }

            public function getEcouteurs(): array
            {
                return [
                    VisiteEnregistree::class => [
                        function (VisiteEnregistree $event) {
                            self::$lastVisiteId = $event->visite->id;
                        },
                    ],
                ];
            }
        };

        // When plugin is registered, listeners are attached
        $plugin->enregistrer();

        $service = app(TrackingService::class);
        $request = Request::create('/tracker.js', 'POST');

        $visite = $service->enregistrerVisite($this->site, [
            'url' => 'https://example.com/blog',
            'chemin' => '/blog',
        ], $request);

        $this->assertNotNull($visite);
        $this->assertSame($visite->id, $plugin::$lastVisiteId);
    }

    public function test_plugin_activation_and_deactivation_events_are_dispatched(): void
    {
        Event::fake([
            PluginActive::class,
            PluginDesactive::class,
        ]);

        $pluginDir = base_path('plugins/EventTestPlugin');
        File::ensureDirectoryExists($pluginDir.'/src');

        $manifest = [
            'identifiant' => 'event-test-plugin',
            'nom' => 'Event Test Plugin',
            'version' => '1.0.0',
            'classe' => 'Plugins\\EventTestPlugin\\EventTestPlugin',
        ];

        File::put($pluginDir.'/manifest.json', json_encode($manifest));

        $phpCode = <<<'PHP'
<?php
namespace Plugins\EventTestPlugin;

use App\Core\Plugin\AbstractPlugin;

class EventTestPlugin extends AbstractPlugin
{
    public function getIdentifiant(): string { return 'event-test-plugin'; }
    public function getNom(): string { return 'Event Test Plugin'; }
    public function getVersion(): string { return '1.0.0'; }
}
PHP;

        File::put($pluginDir.'/src/EventTestPlugin.php', $phpCode);

        try {
            $manager = app(PluginManager::class);
            $manager->reinitialiser();

            $manager->activer('event-test-plugin');
            Event::assertDispatched(PluginActive::class, function (PluginActive $event) {
                return $event->identifiant === 'event-test-plugin';
            });

            $manager->desactiver('event-test-plugin');
            Event::assertDispatched(PluginDesactive::class, function (PluginDesactive $event) {
                return $event->identifiant === 'event-test-plugin';
            });
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }
}
