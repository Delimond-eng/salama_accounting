<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeFondsHistorique extends Model
{
    protected $table = 'demande_fonds_historiques';

    protected $fillable = [
        'demande_fonds_id', 'user_id', 'action', 'description', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeFonds::class, 'demande_fonds_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
