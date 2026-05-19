<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facture extends Model
{
    use SoftDeletes;

    public const TYPE_VENTE_CLIENT = 'vente_client';

    public const TYPE_ACHAT_FOURNISSEUR = 'achat_fournisseur';

    public const TYPE_AVOIR_CLIENT = 'avoir_client';

    public const TYPE_AVOIR_FOURNISSEUR = 'avoir_fournisseur';

    public const STATUT_BROUILLON = 'brouillon';

    public const STATUT_VALIDEE = 'validee';

    public const STATUT_PAYEE = 'payee';

    public const STATUT_ANNULEE = 'annulee';

    protected $fillable = [
        'societe_id', 'exercice_id', 'type_document', 'numero', 'tiers_id',
        'facture_origine_id', 'date_facture', 'date_echeance', 'statut', 'objet',
        'montant_ht', 'montant_tva', 'montant_ttc', 'taux_tva', 'tva_active', 'devise',
        'ecriture_validation_id', 'notes', 'cree_par', 'valide_par', 'valide_le',
    ];

    protected $casts = [
        'date_facture' => 'date',
        'date_echeance' => 'date',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'tva_active' => 'boolean',
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

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function factureOrigine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'facture_origine_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(FactureLigne::class)->orderBy('ordre');
    }

    public function ecritureValidation(): BelongsTo
    {
        return $this->belongsTo(Ecriture::class, 'ecriture_validation_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }

    public function estModifiable(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function estClient(): bool
    {
        return in_array($this->type_document, [self::TYPE_VENTE_CLIENT, self::TYPE_AVOIR_CLIENT], true);
    }

    public function estAvoir(): bool
    {
        return in_array($this->type_document, [self::TYPE_AVOIR_CLIENT, self::TYPE_AVOIR_FOURNISSEUR], true);
    }
}
