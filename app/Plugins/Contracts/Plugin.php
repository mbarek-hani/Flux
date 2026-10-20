<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

use App\Plugins\Data\PluginManifest;

interface Plugin
{
    public function slug(): string;

    public function name(): string;

    public function version(): string;

    public function register(): void;

    public function enable(): void;

    public function disable(): void;

    public function install(): void;

    public function uninstall(): void;

    public function manifest(): PluginManifest;
}
