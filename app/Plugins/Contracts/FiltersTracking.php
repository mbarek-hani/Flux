<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

use Illuminate\Http\Request;

interface FiltersTracking
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function filterVisit(array $data, Request $request): ?array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function filterEvent(array $data, Request $request): ?array;
}
