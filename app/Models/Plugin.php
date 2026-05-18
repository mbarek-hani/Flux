<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = [
        'identifiant',
        'nom',
        'version',
        'description',
        'auteur',
        'actif',
        'configuration',
        'metadonnees',
        'installe_le',
        'active_le',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'configuration' => 'array',
        'metadonnees' => 'array',
        'installe_le' => 'datetime',
        'active_le' => 'datetime',
    ];

    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    public function scopeInstalles($query)
    {
        return $query->whereNotNull('installe_le');
    }
}
