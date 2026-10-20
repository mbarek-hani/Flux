<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Plugin\PluginAutoloader;
use App\Core\Plugin\PluginDiscovery;
use App\Core\Plugin\PluginMetadata;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginDiscoveryTest extends TestCase
{
    private string $tempPluginsDir;

    private string $tempCacheFile;

    private PluginAutoloader $autoloader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPluginsDir = storage_path('framework/testing/test_plugins');
        $this->tempCacheFile = storage_path('framework/testing/test_plugins_cache.php');

        File::ensureDirectoryExists($this->tempPluginsDir);
        $this->autoloader = new PluginAutoloader;
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

    public function test_discovers_plugins_from_manifest_without_instantiating(): void
    {
        $pluginDir = $this->tempPluginsDir.'/DummyPlugin';
        File::ensureDirectoryExists($pluginDir.'/src');

        $manifest = [
            'identifiant' => 'dummy-plugin',
            'nom' => 'Dummy Plugin',
            'version' => '1.2.0',
            'classe' => 'Plugins\\DummyPlugin\\DummyPlugin',
            'description' => 'Un plugin factice pour les tests.',
            'auteur' => 'Flux Tester',
            'licence' => 'MIT',
        ];

        File::put($pluginDir.'/manifest.json', json_encode($manifest));

        $discovery = new PluginDiscovery(
            $this->autoloader,
            $this->tempPluginsDir,
            $this->tempCacheFile,
        );

        $discovered = $discovery->decouvrir();

        $this->assertArrayHasKey('dummy-plugin', $discovered);
        $meta = $discovered['dummy-plugin'];
        $this->assertInstanceOf(PluginMetadata::class, $meta);
        $this->assertSame('dummy-plugin', $meta->getIdentifiant());
        $this->assertSame('Dummy Plugin', $meta->getNom());
        $this->assertSame('1.2.0', $meta->getVersion());
        $this->assertSame('Un plugin factice pour les tests.', $meta->getDescription());
        $this->assertSame('Flux Tester', $meta->getAuteur());
        $this->assertSame('Plugins\\DummyPlugin', $meta->namespace);

        // Namespace was registered in autoloader
        $this->assertTrue($this->autoloader->hasNamespace('Plugins\\DummyPlugin'));
    }

    public function test_caches_and_loads_from_cache(): void
    {
        $pluginDir = $this->tempPluginsDir.'/CacheablePlugin';
        File::ensureDirectoryExists($pluginDir.'/src');

        $manifest = [
            'identifiant' => 'cacheable-plugin',
            'nom' => 'Cacheable Plugin',
            'version' => '2.0.0',
            'classe' => 'Plugins\\CacheablePlugin\\CacheablePlugin',
        ];

        File::put($pluginDir.'/manifest.json', json_encode($manifest));

        $discovery = new PluginDiscovery(
            $this->autoloader,
            $this->tempPluginsDir,
            $this->tempCacheFile,
        );

        $this->assertFalse($discovery->estEnCache());

        // Cache discovery
        $cachedPlugins = $discovery->mettreEnCache();
        $this->assertCount(1, $cachedPlugins);
        $this->assertTrue($discovery->estEnCache());
        $this->assertFileExists($this->tempCacheFile);

        // Delete manifest file to prove reading from cache
        File::delete($pluginDir.'/manifest.json');

        $freshAutoloader = new PluginAutoloader;
        $cachedDiscovery = new PluginDiscovery(
            $freshAutoloader,
            $this->tempPluginsDir,
            $this->tempCacheFile,
        );

        $results = $cachedDiscovery->decouvrir();
        $this->assertArrayHasKey('cacheable-plugin', $results);
        $this->assertSame('Cacheable Plugin', $results['cacheable-plugin']->getNom());
        $this->assertTrue($freshAutoloader->hasNamespace('Plugins\\CacheablePlugin'));

        // Clear cache
        $cachedDiscovery->viderCache();
        $this->assertFalse($cachedDiscovery->estEnCache());
    }

    public function test_handles_corrupt_manifest_gracefully(): void
    {
        $corruptDir = $this->tempPluginsDir.'/CorruptPlugin';
        File::ensureDirectoryExists($corruptDir);
        File::put($corruptDir.'/manifest.json', 'INVALID JSON {');

        $discovery = new PluginDiscovery(
            $this->autoloader,
            $this->tempPluginsDir,
            $this->tempCacheFile,
        );

        $plugins = $discovery->decouvrir();
        $this->assertEmpty($plugins);
    }

    public function test_loads_vendor_autoload_if_present(): void
    {
        $vendorPluginDir = $this->tempPluginsDir.'/VendorPlugin';
        File::ensureDirectoryExists($vendorPluginDir.'/vendor');
        File::ensureDirectoryExists($vendorPluginDir.'/src');

        File::put($vendorPluginDir.'/vendor/autoload.php', "<?php define('TEST_VENDOR_LOADED_FLAG', true);");

        $manifest = [
            'identifiant' => 'vendor-plugin',
            'nom' => 'Vendor Plugin',
            'classe' => 'Plugins\\VendorPlugin\\VendorPlugin',
        ];

        File::put($vendorPluginDir.'/manifest.json', json_encode($manifest));

        $discovery = new PluginDiscovery(
            $this->autoloader,
            $this->tempPluginsDir,
            $this->tempCacheFile,
        );

        $discovered = $discovery->decouvrir();
        $metadata = $discovered['vendor-plugin'];

        // Instantiating should trigger vendor autoload
        $discovery->instancier($metadata);

        $this->assertTrue(defined('TEST_VENDOR_LOADED_FLAG'));
    }
}
