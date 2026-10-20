<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Plugins\Data\DiscoveredPlugin;
use App\Plugins\ManifestReader;
use App\Plugins\PluginAutoloader;
use App\Plugins\PluginDiscovery;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginDiscoveryTest extends TestCase
{
    private string $pluginsDirectory;

    private string $cacheFile;

    private PluginAutoloader $autoloader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginsDirectory = storage_path('framework/testing/test_plugins');
        $this->cacheFile = storage_path('framework/testing/test_plugins_cache.php');

        File::ensureDirectoryExists($this->pluginsDirectory);
        $this->autoloader = new PluginAutoloader;
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->pluginsDirectory)) {
            File::deleteDirectory($this->pluginsDirectory);
        }

        if (File::exists($this->cacheFile)) {
            File::delete($this->cacheFile);
        }

        parent::tearDown();
    }

    public function test_discovers_plugins_from_manifest_without_instantiating(): void
    {
        $this->writePlugin('DummyPlugin', [
            'slug' => 'dummy-plugin',
            'name' => 'Dummy Plugin',
            'version' => '1.2.0',
            'class' => 'Plugins\\DummyPlugin\\DummyPlugin',
            'description' => 'A dummy plugin used by tests.',
            'author' => 'Flux Tester',
            'license' => 'MIT',
        ]);

        $discovered = $this->discovery()->discover();

        $this->assertArrayHasKey('dummy-plugin', $discovered);

        $plugin = $discovered['dummy-plugin'];
        $this->assertInstanceOf(DiscoveredPlugin::class, $plugin);
        $this->assertSame('dummy-plugin', $plugin->slug());
        $this->assertSame('Dummy Plugin', $plugin->name());
        $this->assertSame('1.2.0', $plugin->version());
        $this->assertSame('A dummy plugin used by tests.', $plugin->manifest->description);
        $this->assertSame('Flux Tester', $plugin->manifest->author);
        $this->assertSame('Plugins\\DummyPlugin', $plugin->namespace());
        $this->assertTrue($this->autoloader->hasNamespace('Plugins\\DummyPlugin'));
    }

    public function test_caches_and_loads_from_cache(): void
    {
        $this->writePlugin('CacheablePlugin', [
            'slug' => 'cacheable-plugin',
            'name' => 'Cacheable Plugin',
            'version' => '2.0.0',
            'class' => 'Plugins\\CacheablePlugin\\CacheablePlugin',
        ]);

        $discovery = $this->discovery();

        $this->assertFalse($discovery->isCached());

        $cached = $discovery->cache();
        $this->assertCount(1, $cached);
        $this->assertTrue($discovery->isCached());
        $this->assertFileExists($this->cacheFile);

        File::delete($this->pluginsDirectory.'/CacheablePlugin/manifest.json');

        $freshAutoloader = new PluginAutoloader;
        $cachedDiscovery = new PluginDiscovery(
            $freshAutoloader,
            new ManifestReader,
            $this->pluginsDirectory,
            $this->cacheFile,
        );

        $results = $cachedDiscovery->discover();
        $this->assertArrayHasKey('cacheable-plugin', $results);
        $this->assertSame('Cacheable Plugin', $results['cacheable-plugin']->name());
        $this->assertTrue($freshAutoloader->hasNamespace('Plugins\\CacheablePlugin'));

        $cachedDiscovery->clearCache();
        $this->assertFalse($cachedDiscovery->isCached());
    }

    public function test_handles_corrupt_manifest_gracefully(): void
    {
        $directory = $this->pluginsDirectory.'/CorruptPlugin';
        File::ensureDirectoryExists($directory);
        File::put($directory.'/manifest.json', 'INVALID JSON {');

        $this->assertEmpty($this->discovery()->discover());
    }

    public function test_ignores_manifests_outside_the_plugins_namespace(): void
    {
        $this->writePlugin('InvalidPlugin', [
            'slug' => 'invalid-plugin',
            'name' => 'Invalid Plugin',
            'class' => 'App\\Plugins\\InvalidPlugin',
        ]);

        $this->assertEmpty($this->discovery()->discover());
    }

    public function test_loads_vendor_autoload_if_present(): void
    {
        $this->writePlugin('VendorPlugin', [
            'slug' => 'vendor-plugin',
            'name' => 'Vendor Plugin',
            'class' => 'Plugins\\VendorPlugin\\VendorPlugin',
        ]);

        File::ensureDirectoryExists($this->pluginsDirectory.'/VendorPlugin/vendor');
        File::put(
            $this->pluginsDirectory.'/VendorPlugin/vendor/autoload.php',
            "<?php define('TEST_VENDOR_LOADED_FLAG', true);",
        );

        $discovered = $this->discovery()->discover();

        $this->discovery()->instantiate($discovered['vendor-plugin']);

        $this->assertTrue(defined('TEST_VENDOR_LOADED_FLAG'));
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function writePlugin(string $folder, array $manifest): void
    {
        $directory = $this->pluginsDirectory.'/'.$folder;
        File::ensureDirectoryExists($directory.'/src');
        File::put($directory.'/manifest.json', json_encode($manifest));
    }

    private function discovery(): PluginDiscovery
    {
        return new PluginDiscovery(
            $this->autoloader,
            new ManifestReader,
            $this->pluginsDirectory,
            $this->cacheFile,
        );
    }
}
