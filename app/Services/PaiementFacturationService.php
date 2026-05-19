<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaiementFacturationService
{
    public function __construct(
        protected FacturationComptableService $comptable,
        protected FacturationService $facturation,
        protected AuditLogService $audit
    ) {}

    public function genererNumero(int $societeId): string
    {
        $prefix = config('facturation.numerotation.paiement.prefix', 'PAY');
        $year = now()->format('Y');

        $last = Paiement::parSociete($societeId)
            ->where('numero', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('numero');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function payerFacture(int $societeId, int $factureId, array $data): Paiement
    {
        return DB::transaction(function () use ($societeId, $factureId, $data) {
            $facture = Facture::parSociete($societeId)->findOrFail($factureId);

            if ($facture->statut !== Facture::STATUT_VALIDEE) {
                throw new InvalidArgumentException('Seules les factures validées peuvent être payées.');
            }

            $montant = (float) ($data['montant'] ?? $facture->montant_ttc);
            $methode = $data['methode'] ?? 'banque';
            $compteTreso = $data['compte_tresorerie']
                ?? ($methode === 'caisse' ? config('facturation.comptes.caisse') : config('facturation.comptes.banque'));

            $typePaiement = $facture->estClient() ? 'facture_client' : 'facture_fournisseur';

            $paiement = Paiement::create([
                'societe_id' => $societeId,
                'type_paiement' => $typePaiement,
                'facture_id' => $facture->id,
                'numero' => $this->genererNumero($societeId),
                'montant' => $montant,
                'devise' => $data['devise'] ?? $facture->devise,
                'methode' => $methode,
                'compte_tresorerie' => $compteTreso,
                'date_paiement' => $data['date_paiement'] ?? now()->toDateString(),
                'statut' => Paiement::STATUT_BROUILLON,
                'user_id' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $result = $this->comptable->ecriturePaiement($paiement, $facture);

            $paiement->update([
                'statut' => Paiement::STATUT_VALIDE,
                'ecriture_id' => $result['ecriture']->id,
            ]);

            $facture->update(['statut' => Facture::STATUT_PAYEE]);

            $this->audit->log(
                'paiement',
                'facture',
                $facture->id,
                $facture->numero,
                "Paiement {$paiement->numero} — {$facture->numero}",
                ['paiement_id' => $paiement->id, 'montant' => $montant],
                $societeId
            );

            return $paiement->fresh(['ecriture', 'facture']);
        });
    }
}
