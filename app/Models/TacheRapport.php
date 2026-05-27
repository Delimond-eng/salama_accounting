<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TacheRapport extends Model
{
    protected $fillable = ['tache_id', 'user_id', 'contenu'];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fichiers(): HasMany
    {
        return $this->hasMany(TacheFichier::class, 'rapport_id');
    }
}
