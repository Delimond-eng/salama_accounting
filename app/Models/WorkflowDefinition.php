<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    protected $fillable = [
        'societe_id', 'code', 'libelle', 'type_workflow', 'actif', 'est_defaut',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'est_defaut' => 'boolean',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(WorkflowEtape::class)->orderBy('ordre');
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }
}
