<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Plugins\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackerJsController extends Controller
{
    public function __invoke(Request $request, PluginManager $manager): Response
    {
        $coreJs = (string) file_get_contents(resource_path('js/tracker-core.js'));
        $pluginJs = $manager->trackerScript();

        $js = str_replace('/* PLUGIN_INJECTION */', $pluginJs, $coreJs);
        $etag = '"'.md5($js).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=3600, must-revalidate');
        }

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=3600, must-revalidate');
    }
}
