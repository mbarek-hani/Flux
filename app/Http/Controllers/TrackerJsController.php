<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Plugins\PluginManager;
use Illuminate\Http\Response;

class TrackerJsController extends Controller
{
    public function __invoke(PluginManager $manager): Response
    {
        $coreJs = file_get_contents(resource_path('js/tracker-core.js'));

        $pluginJs = $manager->trackerScript();

        $js = str_replace('/* PLUGIN_INJECTION */', $pluginJs, $coreJs);

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
