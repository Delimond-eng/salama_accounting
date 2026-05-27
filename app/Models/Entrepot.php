<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrepot extends Model
{
    protected $fillable = ['societe_id', 'code', 'libelle', 'adresse', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function scopeParSociete($query, int $societeId)
    {
        return $query->where('societe_id', $societeId);
    }
}
