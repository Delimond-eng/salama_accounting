<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeclarationFiscale extends Model
{
    protected $table = 'declarations_fiscales';

    protected $fillable = [
        'societe_id', 'exercice_id', 'type', 'periode_debut', 'periode_fin',
        'date_limite_depot', 'date_depot_effectif', 'base_imposable',
        'tva_collectee', 'tva_deductible', 'tva_nette', 'montant_impot',
        'credit_reporte', 'statut', 'num_quittance', 'notes', 'etabli_par',
    ];

    protected $casts = [
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'date_limite_depot' => 'date',
        'date_depot_effectif' => 'date',
        'base_imposable' => 'float',
        'tva_collectee' => 'float',
        'tva_deductible' => 'float',
        'tva_nette' => 'float',
        'montant_impot' => 'float',
        'credit_reporte' => 'float',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }
}
