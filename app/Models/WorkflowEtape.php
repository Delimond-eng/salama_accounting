<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowEtape extends Model
{
    public const TYPE_INITIATEUR = 'initiateur';

    public const TYPE_COMPTABLE = 'comptable';

    public const TYPE_VALIDATEUR = 'validateur';

    public const TYPE_CAISSIER = 'caissier';

    protected $fillable = [
        'workflow_definition_id', 'ordre', 'code', 'libelle', 'type_etape',
        'role_requis', 'imputation_comptable', 'execution_paiement', 'actif',
    ];

    protected $casts = [
        'imputation_comptable' => 'boolean',
        'execution_paiement' => 'boolean',
        'actif' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }
}
