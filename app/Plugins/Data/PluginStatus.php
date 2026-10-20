<?php

declare(strict_types=1);

namespace App\Plugins\Data;

enum PluginStatus: string
{
    case Discovered = 'discovered';
    case Installed = 'installed';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Failed = 'failed';
}
