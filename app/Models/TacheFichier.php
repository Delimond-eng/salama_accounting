<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TacheFichier extends Model
{
    protected $fillable = [
        'tache_id', 'rapport_id', 'user_id', 'chemin', 'nom_fichier', 'mime', 'taille',
    ];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(TacheRapport::class, 'rapport_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function urlTelechargement(): string
    {
        return route('accounting.taches.fichier.download', ['id' => $this->id]);
    }
}
