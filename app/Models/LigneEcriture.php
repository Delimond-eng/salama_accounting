<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class LigneEcriture extends Model
{
    protected $table = 'lignes_ecritures';

    protected $fillable = ['ecriture_id', 'societe_id', 'exercice_id', 'journal_id', 'num_compte', 'compte_id', 'tiers_id', 'date_ecriture', 'libelle', 'debit', 'credit', 'devise', 'montant_devise', 'taux_change', 'lettre', 'date_lettrage', 'lettre_par', 'pointage', 'date_pointage', 'axe_analytique_id', 'section_analytique_id', 'ordre', 'reference_ligne'];

    protected $casts = [
        'date_ecriture' => 'date', 'debit' => 'float', 'credit' => 'float',
        'montant_devise' => 'float', 'taux_change' => 'float',
        'date_lettrage' => 'date', 'date_pointage' => 'date', 'ordre' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $ligne) {
            $debit = (float) $ligne->debit;
            $credit = (float) $ligne->credit;
            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('Une ligne ne peut pas avoir débit ET crédit > 0.');
            }
        });
    }

    public function ecriture(): BelongsTo
    {
        return $this->belongsTo(Ecriture::class);
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_id');
    }

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function sectionAnalytique(): BelongsTo
    {
        return $this->belongsTo(SectionAnalytique::class, 'section_analytique_id');
    }

    public function axeAnalytique(): BelongsTo
    {
        return $this->belongsTo(AxeAnalytique::class, 'axe_analytique_id');
    }

    public function analytiques(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LigneEcritureAnalytique::class, 'ligne_ecriture_id');
    }
}
