<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanComptableAxe extends Model
{
    protected $table = 'plan_comptable_axes';

    protected $fillable = ['societe_id', 'plan_comptable_id', 'axe_analytique_id'];

    public function compte(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'plan_comptable_id');
    }

    public function axe(): BelongsTo
    {
        return $this->belongsTo(AxeAnalytique::class, 'axe_analytique_id');
    }
}
