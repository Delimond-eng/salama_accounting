<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemandeFonds extends Model
{
    use SoftDeletes;

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_EN_VALIDATION = 'en_validation';

    public const STATUT_APPROUVEE = 'approuvee';

    public const STATUT_REJETEE = 'rejetee';

    public const STATUT_EXECUTEE = 'executee';

    protected $table = 'demandes_fonds';

    protected $fillable = [
        'societe_id', 'workflow_definition_id', 'workflow_etape_courante_id',
        'numero', 'demandeur_id', 'montant', 'devise', 'motif', 'journal_id',
        'statut', 'compte_debit', 'compte_credit', 'ecriture_id', 'motif_rejet',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function etapeCourante(): BelongsTo
    {
        return $this->belongsTo(WorkflowEtape::class, 'workflow_etape_courante_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function ecriture(): BelongsTo
    {
        return $this->belongsTo(Ecriture::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(DemandeFondsValidation::class);
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(DemandeFondsHistorique::class)->orderByDesc('created_at');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }
}
