<?php

declare(strict_types=1);

namespace App\Plugins\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $table = 'plugins';

    protected $fillable = [
        'slug',
        'name',
        'version',
        'description',
        'author',
        'license',
        'is_active',
        'configuration',
        'manifest',
        'status',
        'installed_at',
        'enabled_at',
        'disabled_at',
        'last_error',
        'last_error_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'configuration' => 'array',
        'manifest' => 'array',
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
        'last_error_at' => 'datetime',
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
