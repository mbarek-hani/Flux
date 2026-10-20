<?php

declare(strict_types=1);

namespace App\Analytics\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'tracking_token',
        'is_active',
        'ignore_bots',
        'ignore_dnt',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ignore_bots' => 'boolean',
        'ignore_dnt' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Site $site): void {
            if (empty($site->tracking_token)) {
                $site->tracking_token = hash('sha256', Str::random(40));
            }
        });
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'site_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'site_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getTrackingScript(): string
    {
        $url = rtrim(config('app.url'), '/');
        $token = $this->tracking_token;

        return '<script defer src="'.$url.'/tracker.js" data-token="'.$token.'"></script>';
    }
}
