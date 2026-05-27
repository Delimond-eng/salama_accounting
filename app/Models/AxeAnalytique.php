<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AxeAnalytique extends Model
{
    protected $table = 'axes_analytiques';

    protected $fillable = [
        'societe_id', 'code', 'libelle', 'description', 'actif', 'ordre_affichage',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre_affichage' => 'integer',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SectionAnalytique::class, 'axe_analytique_id');
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
