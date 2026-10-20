<?php

declare(strict_types=1);

namespace App\Plugins\Models;

use Illuminate\Database\Eloquent\Model;

class PluginMigration extends Model
{
    protected $table = 'plugin_migrations';

    protected $fillable = [
        'plugin_slug',
        'migration',
        'batch',
        'ran_at',
    ];

    protected $casts = [
        'batch' => 'integer',
        'ran_at' => 'datetime',
    ];
}
