<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacheEtape extends Model
{
    protected $fillable = [
        'tache_id', 'user_id', 'libelle', 'ordre', 'est_terminee', 'terminee_le', 'terminee_par',
    ];

    protected $casts = [
        'est_terminee' => 'boolean',
        'terminee_le' => 'datetime',
    ];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function assigne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function terminePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminee_par');
    }
}
