<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanComptable extends Model
{
    use SoftDeletes;

    protected $table = 'plan_comptable';

    protected $fillable = [
        'societe_id', 'num_compte', 'libelle', 'libelle_abrege', 'classe', 'num_compte_parent', 'niveau',
        'type_compte', 'type_compte_detail', 'sens_normal', 'categorie_bilan',
        'est_compte_detail', 'est_compte_tiers', 'est_lettrable', 'est_rapprochable',
        'est_budgetaire', 'exige_piece_jointe', 'multi_devises', 'exige_analytique',
        'type_tva', 'taux_tva_defaut', 'actif', 'est_systeme', 'notes',
    ];

    protected $casts = [
        'classe' => 'integer', 'niveau' => 'integer',
        'est_compte_detail' => 'boolean', 'est_compte_tiers' => 'boolean',
        'est_lettrable' => 'boolean', 'est_rapprochable' => 'boolean',
        'est_budgetaire' => 'boolean', 'exige_piece_jointe' => 'boolean',
        'multi_devises' => 'boolean', 'exige_analytique' => 'boolean',
        'actif' => 'boolean', 'est_systeme' => 'boolean', 'taux_tva_defaut' => 'float',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function lignesEcritures(): HasMany
    {
        return $this->hasMany(LigneEcriture::class, 'compte_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeParSociete($query, ?int $societeId)
    {
        return $query->where(function ($q) use ($societeId) {
            $q->whereNull('societe_id');
            if ($societeId) {
                $q->orWhere('societe_id', $societeId);
            }
        });
    }
}
