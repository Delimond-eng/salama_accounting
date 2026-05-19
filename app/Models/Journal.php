<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model
{
    use SoftDeletes;

    protected $table = 'journaux';

    protected $fillable = [
        'societe_id', 'code', 'libelle', 'type', 'compte_contrepartie', 'prefixe_piece',
        'prochain_numero', 'format_numerotation', 'padding_numero', 'saisie_tiers_obligatoire',
        'saisie_lettrage_auto', 'mode_brouillard', 'devise_defaut', 'actif', 'ordre_affichage',
    ];

    protected $casts = [
        'prochain_numero' => 'integer', 'padding_numero' => 'integer', 'ordre_affichage' => 'integer',
        'saisie_tiers_obligatoire' => 'boolean', 'saisie_lettrage_auto' => 'boolean',
        'mode_brouillard' => 'boolean', 'actif' => 'boolean',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(Ecriture::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
