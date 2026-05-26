<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BonCommande extends Model
{
    use SoftDeletes;

    protected $table = 'bons_commande';

    protected $fillable = [
        'societe_id', 'tiers_id', 'numero', 'date_commande', 'date_livraison_prevue',
        'statut', 'devise', 'montant_ht', 'montant_ttc', 'notes', 'cree_par',
    ];

    protected $casts = [
        'date_commande' => 'date',
        'date_livraison_prevue' => 'date',
        'montant_ht' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
    ];

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(BonCommandeLigne::class)->orderBy('ordre');
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }
}
