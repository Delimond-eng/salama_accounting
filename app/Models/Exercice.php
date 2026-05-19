<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercice extends Model
{
    use SoftDeletes;

    protected $table = 'exercices';

    protected $fillable = [
        'societe_id', 'libelle', 'annee', 'date_debut', 'date_fin', 'statut', 'est_courant',
        'date_ouverture', 'date_cloture', 'cloture_par', 'notes_cloture',
        'report_a_nouveau_genere', 'bilan_ouverture_genere',
    ];

    protected $casts = [
        'annee' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_ouverture' => 'date',
        'date_cloture' => 'date',
        'est_courant' => 'boolean',
        'report_a_nouveau_genere' => 'boolean',
        'bilan_ouverture_genere' => 'boolean',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function accepteEcritures(?string $typeEcriture = 'normale'): bool
    {
        return match ($this->statut) {
            'ouvert' => true,
            'pre_cloture' => in_array($typeEcriture, ['inventaire', 'cloture', 'ouverture'], true),
            default => false,
        };
    }
}
