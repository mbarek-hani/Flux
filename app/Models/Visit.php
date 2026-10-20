<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'session_id',
        'url',
        'path',
        'title',
        'referrer',
        'referrer_domain',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'browser',
        'browser_version',
        'operating_system',
        'device',
        'country_code',
        'country_name',
        'session_duration',
        'is_bounce',
        'is_new_session',
        'created_at',
    ];

    protected $casts = [
        'is_bounce' => 'boolean',
        'is_new_session' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'visit_id');
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(VisitMetadata::class, 'visit_id');
    }

    public function scopePeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}
