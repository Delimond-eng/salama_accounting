<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Devise extends Model
{
    protected $table = 'devises';

    protected $fillable = [
        'code_iso', 'libelle', 'symbole', 'pays', 'nb_decimales', 'est_devise_reference', 'actif',
    ];

    protected $casts = [
        'nb_decimales' => 'integer', 'est_devise_reference' => 'boolean', 'actif' => 'boolean',
    ];

    public function tauxChanges(): HasMany
    {
        return $this->hasMany(TauxChange::class, 'devise_code', 'code_iso');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
