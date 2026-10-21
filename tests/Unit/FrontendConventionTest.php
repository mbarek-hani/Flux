<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrontendConventionTest extends TestCase
{
    public function test_blade_views_do_not_use_alpine_or_inline_handlers(): void
    {
        $patterns = [
            'Alpine directive' => '/\bx-(?:data|show|text|for|if|model|init|ref|cloak|transition|on)\b/',
            'Alpine event handler' => '/\b@(?:click|change|submit|keydown|keyup|input)\b/',
            'inline style attribute' => '/\sstyle\s*=/',
            'inline event attribute' => '/\son(?:click|change|submit|load|error)\s*=/i',
        ];

        $violations = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $contents = File::get($file->getPathname());

            foreach ($patterns as $label => $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    $violations[] = $file->getRelativePathname().': '.$label;
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_frontend_entry_point_does_not_boot_alpine(): void
    {
        $entryPoint = File::get(resource_path('js/app.js'));

        $this->assertStringNotContainsString('Alpine', $entryPoint);
        $this->assertStringNotContainsString('x-cloak', File::get(resource_path('views/layouts/partials/head.blade.php')));
    }

    public function test_alpine_is_not_a_frontend_dependency(): void
    {
        $package = json_decode(
            File::get(base_path('package.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertArrayNotHasKey('alpinejs', $package['dependencies'] ?? []);
        $this->assertArrayNotHasKey('alpinejs', $package['devDependencies'] ?? []);
    }
}
