<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionAnalytique extends Model
{
    protected $table = 'sections_analytiques';

    protected $fillable = [
        'axe_analytique_id', 'societe_id', 'code', 'libelle', 'parent_id', 'budget', 'actif',
    ];

    protected $casts = [
        'budget' => 'float',
        'actif' => 'boolean',
    ];

    public function axe(): BelongsTo
    {
        return $this->belongsTo(AxeAnalytique::class, 'axe_analytique_id');
    }

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
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
