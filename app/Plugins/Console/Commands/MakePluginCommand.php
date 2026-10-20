<?php

declare(strict_types=1);

namespace App\Plugins\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePluginCommand extends Command
{
    protected $signature = 'make:plugin {name}';

    protected $description = 'Scaffold a new plugin';

    public function handle(): int
    {
        $name = Str::studly((string) $this->argument('name'));
        $slug = Str::kebab($name);
        $path = base_path('plugins/'.$name);

        if (is_dir($path)) {
            $this->error("Plugin [{$name}] already exists.");

            return self::FAILURE;
        }

        foreach (['src', 'routes', 'resources/views', 'resources/assets', 'database/migrations'] as $directory) {
            File::ensureDirectoryExists($path.'/'.$directory);
        }

        $manifest = [
            'slug' => $slug,
            'name' => Str::headline($name),
            'version' => '1.0.0',
            'description' => '',
            'author' => 'Unknown',
            'license' => 'MIT',
            'class' => "Plugins\\{$name}\\{$name}Plugin",
            'hooks' => [],
            'tabs' => [],
            'navigation' => [],
            'settings' => [],
            'routes' => [
                'web' => ['middleware' => ['web', 'auth'], 'prefix' => 'plugins/'.$slug, 'name' => 'plugins.'.$slug.'.'],
                'api' => ['middleware' => ['api'], 'prefix' => 'api/plugins/'.$slug, 'name' => 'api.plugins.'.$slug.'.'],
            ],
        ];

        File::put($path.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        File::put($path.'/src/'.$name.'Plugin.php', $this->pluginStub($name));

        File::put($path.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/', fn () => view('{$slug}::index'))->name('index');\n");
        File::put($path.'/routes/api.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/', fn () => response()->json(['plugin' => '{$slug}']));\n");
        File::put($path.'/resources/views/index.blade.php', "<x-app-layout>\n    <x-slot name=\"titre\">".Str::headline($name)."</x-slot>\n\n    <div class=\"p-page\">\n        <div class=\"p-container\">\n            <h2 class=\"p-page__title\">".Str::headline($name)."</h2>\n        </div>\n    </div>\n</x-app-layout>\n");
        File::put($path.'/resources/assets/style.css', '');
        File::put($path.'/resources/assets/plugin.js', '');

        $this->info("Plugin [{$name}] created at plugins/{$name}.");

        return self::SUCCESS;
    }

    private function pluginStub(string $name): string
    {
        $slug = Str::kebab($name);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$name};

use App\\Plugins\\AbstractPlugin;

class {$name}Plugin extends AbstractPlugin
{
    public function slug(): string
    {
        return '{$slug}';
    }

    public function name(): string
    {
        return '{$name}';
    }

    public function version(): string
    {
        return '1.0.0';
    }
}
PHP;
    }
}
