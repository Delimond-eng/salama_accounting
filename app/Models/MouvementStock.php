<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementStock extends Model
{
    public const TYPE_ENTREE = 'entree';

    public const TYPE_SORTIE = 'sortie';

    public const TYPE_AJUSTEMENT = 'ajustement';

    public const TYPE_INVENTAIRE = 'inventaire';

    protected $table = 'mouvements_stock';

    protected $fillable = [
        'societe_id', 'numero', 'produit_id', 'entrepot_id', 'type_mouvement', 'quantite',
        'stock_avant', 'stock_apres', 'reference_type', 'reference_id', 'libelle',
        'date_mouvement', 'user_id',
    ];

    protected $casts = [
        'quantite' => 'decimal:4',
        'stock_avant' => 'decimal:4',
        'stock_apres' => 'decimal:4',
        'date_mouvement' => 'date',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function entrepot(): BelongsTo
    {
        return $this->belongsTo(Entrepot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }
}
