<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\SocieteContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $reference = null,
        ?string $description = null,
        ?array $metadata = null,
        ?int $societeId = null
    ): AuditLog {
        return AuditLog::create([
            'societe_id' => $societeId ?? SocieteContext::id(),
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reference' => $reference,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
        ]);
    }

    public function logEcriture(string $action, $ecriture, ?string $extra = null): AuditLog
    {
        $libelle = $ecriture->libelle ?? '';
        $piece = $ecriture->num_piece ?? '';

        return $this->log(
            $action,
            'ecriture',
            $ecriture->id,
            $piece,
            trim("{$action} — {$piece} {$libelle}".($extra ? " ({$extra})" : '')),
            [
                'statut' => $ecriture->statut,
                'journal_id' => $ecriture->journal_id,
                'total_debit' => $ecriture->total_debit,
                'total_credit' => $ecriture->total_credit,
            ],
            $ecriture->societe_id
        );
    }
}
