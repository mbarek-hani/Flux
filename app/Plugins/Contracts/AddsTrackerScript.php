<?php

declare(strict_types=1);

namespace App\Plugins\Contracts;

interface AddsTrackerScript
{
    public function trackerScript(): ?string;
}
