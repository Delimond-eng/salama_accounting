<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'societe_id', 'code', 'libelle', 'prix_unitaire',
        'compte_vente', 'compte_achat', 'actif',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'actif' => 'boolean',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }
}
