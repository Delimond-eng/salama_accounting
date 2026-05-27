<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureLigne extends Model
{
    protected $fillable = [
        'facture_id', 'produit_id', 'ordre', 'rubrique', 'est_rubrique', 'libelle',
        'quantite', 'prix_unitaire', 'montant_ht', 'compte_comptable', 'section_analytique_id',
    ];

    protected $casts = [
        'est_rubrique' => 'boolean',
        'quantite' => 'decimal:4',
        'prix_unitaire' => 'decimal:2',
        'montant_ht' => 'decimal:2',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
