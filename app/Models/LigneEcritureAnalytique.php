<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneEcritureAnalytique extends Model
{
    protected $table = 'lignes_ecritures_analytiques';

    protected $fillable = [
        'ligne_ecriture_id', 'ecriture_id', 'societe_id', 'exercice_id', 'journal_id',
        'axe_analytique_id', 'section_analytique_id', 'montant', 'pourcentage',
    ];

    protected $casts = [
        'montant' => 'float',
        'pourcentage' => 'float',
    ];

    public function ligneEcriture(): BelongsTo
    {
        return $this->belongsTo(LigneEcriture::class, 'ligne_ecriture_id');
    }

    public function ecriture(): BelongsTo
    {
        return $this->belongsTo(Ecriture::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SectionAnalytique::class, 'section_analytique_id');
    }

    public function axe(): BelongsTo
    {
        return $this->belongsTo(AxeAnalytique::class, 'axe_analytique_id');
    }
}
