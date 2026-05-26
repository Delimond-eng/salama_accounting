<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonLivraisonLigne extends Model
{
    protected $table = 'bon_livraison_lignes';

    protected $fillable = [
        'bon_livraison_id', 'produit_id', 'ordre', 'libelle', 'quantite', 'prix_unitaire',
    ];

    protected $casts = [
        'quantite' => 'decimal:4',
        'prix_unitaire' => 'decimal:4',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
