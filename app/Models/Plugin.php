<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'version',
        'description',
        'author',
        'is_active',
        'configuration',
        'metadata',
        'installed_at',
        'activated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'configuration' => 'array',
        'metadata' => 'array',
        'installed_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInstalled(Builder $query): Builder
    {
        return $query->whereNotNull('installed_at');
    }
}
