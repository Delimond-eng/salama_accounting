<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonCommandeLigne extends Model
{
    protected $table = 'bon_commande_lignes';

    protected $fillable = [
        'bon_commande_id', 'produit_id', 'ordre', 'libelle', 'quantite', 'prix_unitaire', 'montant_ht',
    ];

    protected $casts = [
        'quantite' => 'decimal:4',
        'prix_unitaire' => 'decimal:4',
        'montant_ht' => 'decimal:2',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
