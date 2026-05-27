<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tache extends Model
{
    protected $fillable = [
        'societe_id', 'titre', 'description', 'statut', 'cree_par', 'date_echeance',
    ];

    protected $casts = [
        'date_echeance' => 'date',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(TacheEtape::class)->orderBy('ordre');
    }

    public function rapports(): HasMany
    {
        return $this->hasMany(TacheRapport::class)->orderByDesc('created_at');
    }

    public function fichiers(): HasMany
    {
        return $this->hasMany(TacheFichier::class)->orderByDesc('created_at');
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }

    public function progression(): array
    {
        $total = $this->etapes->count();
        if ($total === 0) {
            return ['total' => 0, 'faites' => 0, 'pourcent' => 0];
        }
        $faites = $this->etapes->where('est_terminee', true)->count();

        return [
            'total' => $total,
            'faites' => $faites,
            'pourcent' => (int) round(($faites / $total) * 100),
        ];
    }
}
