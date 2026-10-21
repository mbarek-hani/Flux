<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Plugins\Data\PluginStatus;
use App\Plugins\HookRegistry;
use App\Plugins\ManifestReader;
use App\Plugins\PluginAutoloader;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use App\Plugins\PluginStateRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginSystemTest extends TestCase
{
    use RefreshDatabase;

    private string $pluginDirectory;

    private string $pluginClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginDirectory = base_path('plugins/TestPluginAlpha');
        $this->pluginClass = 'Plugins\\TestPluginAlpha\\TestPluginAlpha';

        File::ensureDirectoryExists($this->pluginDirectory.'/src');
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->pluginDirectory)) {
            File::deleteDirectory($this->pluginDirectory);
        }

        $cache = base_path('bootstrap/cache/plugins.php');

        if (File::exists($cache)) {
            File::delete($cache);
        }

        $testCache = base_path('bootstrap/cache/plugins-test.php');

        if (File::exists($testCache)) {
            File::delete($testCache);
        }

        parent::tearDown();
    }

    public function test_plugin_cache_artisan_commands(): void
    {
        $this->createPlugin();

        $this->artisan('plugin:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('plugin(s) cached');

        $this->assertFileExists(base_path('bootstrap/cache/plugins.php'));

        $this->artisan('plugin:clear')
            ->assertSuccessful()
            ->expectsOutputToContain('Plugin caches cleared');

        $this->assertFileDoesNotExist(base_path('bootstrap/cache/plugins.php'));
    }

    public function test_plugin_controller_index_lists_discovered_plugins(): void
    {
        $this->createPlugin();
        app(PluginManager::class)->reset();

        $response = $this->actingAs(User::factory()->create())->get('/plugins');

        $response->assertOk();
        $response->assertSee('Test Plugin Alpha');
        $response->assertSee('v1.0.0');
        $response->assertSee('Inactive');
    }

    public function test_inactive_plugin_classes_are_not_instantiated_on_boot(): void
    {
        $pluginDir = base_path('plugins/BrokenInactivePlugin');
        File::ensureDirectoryExists($pluginDir.'/src');

        File::put($pluginDir.'/manifest.json', json_encode([
            'slug' => 'broken-plugin',
            'name' => 'Broken Plugin',
            'version' => '1.0.0',
            'class' => 'Plugins\\BrokenInactivePlugin\\BrokenInactivePlugin',
        ]));

        File::put($pluginDir.'/src/BrokenInactivePlugin.php', <<<'PHP'
<?php

namespace Plugins\BrokenInactivePlugin;

use App\Plugins\AbstractPlugin;

class BrokenInactivePlugin extends AbstractPlugin
{
    public function __construct()
    {
        throw new \RuntimeException('Inactive plugins must not be instantiated.');
    }

    public function slug(): string { return 'broken-plugin'; }
    public function name(): string { return 'Broken Plugin'; }
    public function version(): string { return '1.0.0'; }
}
PHP);

        try {
            $manager = $this->isolatedManager();
            $manager->initialize();

            $this->assertFalse($manager->isActive('broken-plugin'));
            $this->assertArrayHasKey('broken-plugin', $manager->discovered());
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    public function test_enabling_and_disabling_a_plugin(): void
    {
        $this->createPlugin();
        app(PluginManager::class)->reset();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/plugins/test-plugin-alpha/enable')
            ->assertRedirect('/plugins');

        $this->assertDatabaseHas('plugins', [
            'slug' => 'test-plugin-alpha',
            'is_active' => true,
            'status' => PluginStatus::Enabled->value,
        ]);

        $this->actingAs($user)
            ->post('/plugins/test-plugin-alpha/disable')
            ->assertRedirect('/plugins');

        $this->assertDatabaseHas('plugins', [
            'slug' => 'test-plugin-alpha',
            'is_active' => false,
            'status' => PluginStatus::Disabled->value,
        ]);
    }

    public function test_failed_plugin_is_marked_as_failed_instead_of_crashing(): void
    {
        $this->createPlugin('FailingPlugin', 'failing-plugin', throwOnRegister: true);
        app(PluginManager::class)->reset();

        try {
            $this->actingAs(User::factory()->create())
                ->post('/plugins/failing-plugin/enable')
                ->assertRedirect('/plugins');

            $this->assertDatabaseHas('plugins', [
                'slug' => 'failing-plugin',
                'is_active' => false,
                'status' => PluginStatus::Failed->value,
            ]);
        } finally {
            File::deleteDirectory(base_path('plugins/FailingPlugin'));
        }
    }

    public function test_settings_are_validated_against_the_manifest_schema(): void
    {
        $directory = base_path('plugins/SettingsPlugin');
        File::ensureDirectoryExists($directory.'/src');

        File::put($directory.'/manifest.json', json_encode([
            'slug' => 'settings-plugin',
            'name' => 'Settings Plugin',
            'version' => '1.0.0',
            'class' => 'Plugins\\SettingsPlugin\\SettingsPlugin',
            'settings' => [
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ],
        ]));

        File::put($directory.'/src/SettingsPlugin.php', <<<'PHP'
<?php

namespace Plugins\SettingsPlugin;

use App\Plugins\AbstractPlugin;

class SettingsPlugin extends AbstractPlugin
{
    public function slug(): string { return 'settings-plugin'; }
    public function name(): string { return 'Settings Plugin'; }
    public function version(): string { return '1.0.0'; }
}
PHP);

        try {
            $manager = app(PluginManager::class);
            $manager->reset();
            $this->assertTrue($manager->enable('settings-plugin'));

            $this->actingAs(User::factory()->create())
                ->postJson('/settings', [
                    'plugin' => 'settings-plugin',
                    'settings' => ['email' => 'not-an-email'],
                ])
                ->assertStatus(422)
                ->assertJsonPath('error', 'The settings.email field must be a valid email address.');

            $this->actingAs(User::factory()->create())
                ->postJson('/settings', [
                    'plugin' => 'settings-plugin',
                    'settings' => ['email' => 'owner@example.com'],
                ])
                ->assertOk()
                ->assertJsonPath('success', true);

            $this->assertSame(
                'owner@example.com',
                $manager->model('settings-plugin')?->configuration['email'] ?? null,
            );
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_tracker_injects_sandboxed_plugin_scripts_and_uses_etags(): void
    {
        $directory = base_path('plugins/TrackerPlugin');
        File::ensureDirectoryExists($directory.'/src');

        File::put($directory.'/manifest.json', json_encode([
            'slug' => 'tracker-plugin',
            'name' => 'Tracker Plugin',
            'version' => '1.0.0',
            'class' => 'Plugins\\TrackerPlugin\\TrackerPlugin',
        ]));

        File::put($directory.'/src/TrackerPlugin.php', <<<'PHP'
<?php

namespace Plugins\TrackerPlugin;

use App\Plugins\AbstractPlugin;

class TrackerPlugin extends AbstractPlugin
{
    public function slug(): string { return 'tracker-plugin'; }
    public function name(): string { return 'Tracker Plugin'; }
    public function version(): string { return '1.0.0'; }

    public function trackerScript(): ?string
    {
        return 'window.pluginTrackerLoaded = true;';
    }
}
PHP);

        try {
            $manager = app(PluginManager::class);
            $manager->reset();
            $this->assertTrue($manager->enable('tracker-plugin'));

            $response = $this->get('/tracker.js');

            $response->assertOk();
            $response->assertSee('/* Plugin: tracker-plugin */', false);
            $response->assertSee('window.pluginTrackerLoaded = true;', false);
            $response->assertSee('[Flux Plugin Error: tracker-plugin]', false);

            $etag = $response->headers->get('ETag');
            $this->assertNotNull($etag);

            $this->withHeader('If-None-Match', $etag)
                ->get('/tracker.js')
                ->assertStatus(304);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function createPlugin(
        string $folder = 'TestPluginAlpha',
        string $slug = 'test-plugin-alpha',
        bool $throwOnRegister = false,
    ): void {
        $directory = base_path('plugins/'.$folder);
        File::ensureDirectoryExists($directory.'/src');
        $class = 'Plugins\\'.$folder.'\\'.$folder;
        $name = ucwords(str_replace('-', ' ', $slug));

        File::put($directory.'/manifest.json', json_encode([
            'slug' => $slug,
            'name' => $name,
            'version' => '1.0.0',
            'description' => 'Alpha test plugin.',
            'author' => 'Flux Tester',
            'license' => 'MIT',
            'class' => $class,
            'hooks' => [
                ['name' => 'dashboard.widgets', 'type' => 'render', 'priority' => 10],
            ],
        ]));

        $throw = $throwOnRegister ? "        throw new \\RuntimeException('Registration failed.');\n" : '';

        File::put($directory.'/src/'.$folder.'.php', <<<PHP
<?php

namespace Plugins\\{$folder};

use App\\Plugins\\AbstractPlugin;

class {$folder} extends AbstractPlugin
{
    public function slug(): string { return '{$slug}'; }
    public function name(): string { return '{$name}'; }
    public function version(): string { return '1.0.0'; }

    public function register(): void
    {
{$throw}    }
}
PHP);
    }

    private function isolatedManager(): PluginManager
    {
        return new PluginManager(
            new PluginDiscovery(
                new PluginAutoloader,
                app(ManifestReader::class),
                base_path('plugins'),
                base_path('bootstrap/cache/plugins-test.php'),
            ),
            new HookRegistry,
            app(PluginStateRepository::class),
        );
    }
}
