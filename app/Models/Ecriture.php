<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ecriture extends Model
{
    use SoftDeletes;

    protected $table = 'ecritures';

    protected $fillable = [
        'societe_id', 'exercice_id', 'journal_id', 'num_piece', 'num_piece_interne',
        'date_ecriture', 'date_piece', 'date_valeur', 'date_echeance', 'libelle',
        'statut', 'type_ecriture', 'reference_externe', 'reference_facture',
        'total_debit', 'total_credit', 'devise', 'taux_change',
        'cree_par', 'valide_par', 'modifie_par', 'valide_le',
        'ecriture_origine_id', 'date_extourne', 'est_import', 'source_import', 'notes',
    ];

    protected $casts = [
        'date_ecriture' => 'date',
        'date_piece' => 'date',
        'date_valeur' => 'date',
        'date_echeance' => 'date',
        'date_extourne' => 'date',
        'total_debit' => 'float',
        'total_credit' => 'float',
        'taux_change' => 'float',
        'est_import' => 'boolean',
        'valide_le' => 'datetime',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(Exercice::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneEcriture::class)->orderBy('ordre');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function ecritureOrigine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ecriture_origine_id');
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }

    public function estModifiable(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function estEquilibree(): bool
    {
        return abs((float) $this->total_debit - (float) $this->total_credit) < 0.01;
    }
}
