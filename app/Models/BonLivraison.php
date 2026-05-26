<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BonLivraison extends Model
{
    use SoftDeletes;

    protected $table = 'bons_livraison';

    protected $fillable = [
        'societe_id', 'bon_commande_id', 'tiers_id', 'entrepot_id', 'numero',
        'date_livraison', 'statut', 'devise', 'notes', 'cree_par',
    ];

    protected $casts = ['date_livraison' => 'date'];

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(BonLivraisonLigne::class)->orderBy('ordre');
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }
}
