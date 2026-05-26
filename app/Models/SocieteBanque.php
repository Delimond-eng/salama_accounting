<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocieteBanque extends Model
{
    protected $fillable = [
        'societe_id', 'banque', 'numero_compte', 'devise', 'est_defaut', 'ordre',
    ];

    protected $casts = [
        'est_defaut' => 'boolean',
    ];

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }
}
