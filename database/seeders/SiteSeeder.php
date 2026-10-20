<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        Site::create([
            'name' => 'My Blog',
            'domain' => 'myblog.com',
            'tracking_token' => Str::random(64),
            'is_active' => true,
            'ignore_bots' => true,
            'ignore_dnt' => false,
        ]);

        Site::create([
            'name' => 'My Shop',
            'domain' => 'myshop.com',
            'tracking_token' => Str::random(64),
            'is_active' => true,
            'ignore_bots' => true,
            'ignore_dnt' => false,
        ]);

        Site::create([
            'name' => 'Portfolio',
            'domain' => 'portfolio.dev',
            'tracking_token' => Str::random(64),
            'is_active' => false,
            'ignore_bots' => false,
            'ignore_dnt' => true,
        ]);
    }
}
