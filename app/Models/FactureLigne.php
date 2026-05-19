<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureLigne extends Model
{
    protected $fillable = [
        'facture_id', 'produit_id', 'ordre', 'libelle',
        'quantite', 'prix_unitaire', 'montant_ht', 'compte_comptable',
    ];

    protected $casts = [
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
