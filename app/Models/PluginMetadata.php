<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginMetadata extends Model
{
    protected $table = 'plugin_metadata';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'plugin',
        'key',
        'value',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    public static function record(
        int $visitId,
        string $plugin,
        string $key,
        mixed $value,
    ): self {
        return static::updateOrCreate(
            [
                'visit_id' => $visitId,
                'plugin' => $plugin,
                'key' => $key,
            ],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'created_at' => now(),
            ],
        );
    }
}
