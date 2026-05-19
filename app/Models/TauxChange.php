<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TauxChange extends Model
{
    protected $table = 'taux_change';

    protected $fillable = [
        'societe_id', 'devise_code', 'date_taux', 'taux', 'taux_achat', 'taux_vente', 'source',
    ];

    protected $casts = [
        'date_taux' => 'date',
        'taux' => 'float', 'taux_achat' => 'float', 'taux_vente' => 'float',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function devise(): BelongsTo
    {
        return $this->belongsTo(Devise::class, 'devise_code', 'code_iso');
    }
}
