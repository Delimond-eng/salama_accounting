<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeFondsValidation extends Model
{
    protected $table = 'demande_fonds_validations';

    protected $fillable = [
        'demande_fonds_id', 'workflow_etape_id', 'user_id', 'decision', 'commentaire',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeFonds::class, 'demande_fonds_id');
    }

    public function etape(): BelongsTo
    {
        return $this->belongsTo(WorkflowEtape::class, 'workflow_etape_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
