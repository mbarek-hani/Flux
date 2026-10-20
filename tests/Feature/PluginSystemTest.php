<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Plugin\PluginAutoloader;
use App\Core\Plugin\PluginDiscovery;
use App\Core\Plugin\PluginManager;
use App\Models\Plugin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginSystemTest extends TestCase
{
    use RefreshDatabase;

    private string $tempPluginsDir;

    private string $tempCacheFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = base_path('plugins/TestPluginAlpha');
        $this->tempCacheFile = base_path('bootstrap/cache/plugins.php');

        File::ensureDirectoryExists($this->tempPluginsDir.'/src');
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempPluginsDir)) {
            File::deleteDirectory($this->tempPluginsDir);
        }

        if (File::exists($this->tempCacheFile)) {
            File::delete($this->tempCacheFile);
        }

        parent::tearDown();
    }

    public function test_plugin_cache_artisan_commands(): void
    {
        // 1. Run plugin:cache
        $this->artisan('plugin:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('mis en cache avec succès !');

        $this->assertFileExists($this->tempCacheFile);

        // 2. Run plugin:clear
        $this->artisan('plugin:clear')
            ->assertSuccessful()
            ->expectsOutputToContain('Cache des plugins vidé avec succès !');

        $this->assertFileDoesNotExist($this->tempCacheFile);
    }

    public function test_plugin_controller_index_lists_discovered_plugins(): void
    {
        $manifest = [
            'identifiant' => 'test-plugin-alpha',
            'nom' => 'Test Plugin Alpha',
            'version' => '1.0.0',
            'classe' => 'Plugins\\TestPluginAlpha\\TestPluginAlpha',
            'description' => 'Description pour alpha',
            'auteur' => 'Flux Tester',
        ];

        File::put($this->tempPluginsDir.'/manifest.json', json_encode($manifest));

        $user = User::factory()->create();

        // Reinitialize plugin manager
        $manager = app(PluginManager::class);
        $manager->reinitialiser();

        $response = $this->actingAs($user)->get('/plugins');

        $response->assertStatus(200);
        $response->assertSee('Test Plugin Alpha');
        $response->assertSee('v1.0.0');
        $response->assertSee('Inactif');
    }

    public function test_inactive_plugin_classes_are_not_instantiated_on_boot(): void
    {
        // Create an inactive plugin that throws in constructor
        $brokenPluginDir = base_path('plugins/BrokenInactivePlugin');
        File::ensureDirectoryExists($brokenPluginDir.'/src');

        $manifest = [
            'identifiant' => 'broken-plugin',
            'nom' => 'Broken Plugin',
            'version' => '1.0.0',
            'classe' => 'Plugins\\BrokenInactivePlugin\\BrokenInactivePlugin',
        ];

        File::put($brokenPluginDir.'/manifest.json', json_encode($manifest));

        // PHP class with fatal exception in constructor
        $phpCode = <<<'PHP'
<?php
namespace Plugins\BrokenInactivePlugin;

use App\Core\Plugin\AbstractPlugin;

class BrokenInactivePlugin extends AbstractPlugin
{
    public function __construct()
    {
        throw new \RuntimeException('Should not be called if inactive!');
    }

    public function getIdentifiant(): string { return 'broken-plugin'; }
    public function getNom(): string { return 'Broken Plugin'; }
    public function getVersion(): string { return '1.0.0'; }
}
PHP;

        File::put($brokenPluginDir.'/src/BrokenInactivePlugin.php', $phpCode);

        try {
            $autoloader = app(PluginAutoloader::class);
            $discovery = new PluginDiscovery($autoloader);
            $manager = new PluginManager($discovery);

            // Should initialize without throwing exception!
            $manager->initialiser();

            $this->assertFalse($manager->estActif('broken-plugin'));
            $this->assertArrayHasKey('broken-plugin', $manager->getPluginsDecouverts());
        } finally {
            File::deleteDirectory($brokenPluginDir);
        }
    }

    public function test_activating_and_deactivating_plugin(): void
    {
        $manifest = [
            'identifiant' => 'test-plugin-alpha',
            'nom' => 'Test Plugin Alpha',
            'version' => '1.0.0',
            'classe' => 'Plugins\\TestPluginAlpha\\TestPluginAlpha',
        ];

        File::put($this->tempPluginsDir.'/manifest.json', json_encode($manifest));

        $phpCode = <<<'PHP'
<?php
namespace Plugins\TestPluginAlpha;

use App\Core\Plugin\AbstractPlugin;

class TestPluginAlpha extends AbstractPlugin
{
    public function getIdentifiant(): string { return 'test-plugin-alpha'; }
    public function getNom(): string { return 'Test Plugin Alpha'; }
    public function getVersion(): string { return '1.0.0'; }
}
PHP;

        File::put($this->tempPluginsDir.'/src/TestPluginAlpha.php', $phpCode);

        $manager = app(PluginManager::class);
        $manager->reinitialiser();

        $user = User::factory()->create();

        // Activate
        $response = $this->actingAs($user)->post('/plugins/test-plugin-alpha/activer');
        $response->assertRedirect('/plugins');

        $this->assertDatabaseHas('plugins', [
            'identifiant' => 'test-plugin-alpha',
            'actif' => true,
        ]);

        // Deactivate
        $response = $this->actingAs($user)->post('/plugins/test-plugin-alpha/desactiver');
        $response->assertRedirect('/plugins');

        $this->assertDatabaseHas('plugins', [
            'identifiant' => 'test-plugin-alpha',
            'actif' => false,
        ]);
    }
}
