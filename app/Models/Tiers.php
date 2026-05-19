<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tiers extends Model
{
    use SoftDeletes;

    protected $table = 'tiers';

    protected $fillable = [
        'societe_id', 'code', 'nom', 'nom_abrege', 'type', 'num_compte_collectif',
        'forme_juridique', 'rccm', 'num_contribuable', 'num_cnps', 'adresse', 'ville', 'pays',
        'telephone', 'mobile', 'email', 'site_web', 'contact_principal',
        'delai_paiement_jours', 'mode_paiement_defaut', 'plafond_credit', 'devise',
        'actif', 'bloque', 'motif_blocage', 'notes',
    ];

    protected $casts = [
        'delai_paiement_jours' => 'integer', 'plafond_credit' => 'float',
        'actif' => 'boolean', 'bloque' => 'boolean',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
