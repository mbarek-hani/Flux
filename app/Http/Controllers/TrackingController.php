<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Analytics\Models\Site;
use App\Analytics\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    public function __construct(private readonly TrackingService $tracking) {}

    public function track(Request $request): Response|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'size:64'],
            'type' => ['nullable', 'string', 'in:pageview,duration,event'],
            'url' => ['nullable', 'string', 'max:2048'],
            'path' => ['nullable', 'string', 'max:1024'],
            'title' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'referrer_domain' => ['nullable', 'string', 'max:255'],
            'device' => ['nullable', 'string', 'in:desktop,mobile,tablet,unknown'],
            'screen_width' => ['nullable', 'integer'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'name' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'is_navigation' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->emptyResponse();
        }

        $data = $validator->validated();

        $site = Site::query()
            ->where('tracking_token', $data['token'])
            ->where('is_active', true)
            ->first();

        if ($site === null) {
            return $this->emptyResponse();
        }

        match ($data['type'] ?? 'pageview') {
            'duration' => $this->tracking->updateDuration($site, $data, $request),
            'event' => $this->tracking->recordEvent($site, $data, $request),
            default => $this->tracking->recordVisit($site, $data, $request),
        };

        return $this->emptyResponse();
    }

    private function emptyResponse(): Response
    {
        return response()
            ->noContent(204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header(
                'Access-Control-Allow-Headers',
                'Content-Type, Accept, X-Requested-With',
            );
    }
}
