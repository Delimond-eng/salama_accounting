<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produit extends Model
{
    use SoftDeletes;

    public const TYPE_PRODUIT = 'produit';

    public const TYPE_SERVICE = 'service';

    protected $fillable = [
        'societe_id', 'code', 'libelle', 'type_article', 'unite',
        'prix_unitaire', 'prix_unitaire_cdf', 'prix_unitaire_usd',
        'compte_vente', 'compte_achat', 'actif',
        'gestion_stock', 'stock_actuel', 'stock_minimum',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'prix_unitaire_cdf' => 'decimal:2',
        'prix_unitaire_usd' => 'decimal:4',
        'stock_actuel' => 'decimal:4',
        'stock_minimum' => 'decimal:4',
        'gestion_stock' => 'boolean',
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

    public function prixPourDevise(string $devise): float
    {
        return strtoupper($devise) === 'USD'
            ? (float) $this->prix_unitaire_usd
            : (float) ($this->prix_unitaire_cdf ?: $this->prix_unitaire);
    }

    public function estService(): bool
    {
        return $this->type_article === self::TYPE_SERVICE;
    }
}
