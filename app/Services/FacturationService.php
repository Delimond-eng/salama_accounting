<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\Produit;
use App\Models\Tiers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FacturationService
{
    public function __construct(
        protected FacturationComptableService $comptable,
        protected AuditLogService $audit
    ) {}

    public function genererNumero(int $societeId, string $typeDocument): string
    {
        $cfg = config("facturation.numerotation.{$typeDocument}");
        $prefix = $cfg['prefix'] ?? 'DOC';
        $year = now()->format('Y');

        $last = Facture::parSociete($societeId)
            ->where('type_document', $typeDocument)
            ->where('numero', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('numero');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    public function calculerTotaux(array $lignes, bool $tvaActive, float $tauxTva): array
    {
        $ht = 0.0;
        foreach ($lignes as $l) {
            $q = (float) ($l['quantite'] ?? 1);
            $pu = (float) ($l['prix_unitaire'] ?? 0);
            $ht += round($q * $pu, 2);
        }
        $tva = $tvaActive ? round($ht * $tauxTva / 100, 2) : 0.0;

        return [
            'montant_ht' => round($ht, 2),
            'montant_tva' => $tva,
            'montant_ttc' => round($ht + $tva, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $entete
     * @param  array<int, array<string, mixed>>  $lignes
     */
    public function enregistrer(int $societeId, array $entete, array $lignes, ?int $factureId = null): Facture
    {
        return DB::transaction(function () use ($societeId, $entete, $lignes, $factureId) {
            $type = $entete['type_document'];
            $tvaActive = (bool) ($entete['tva_active'] ?? false);
            $tauxTva = (float) ($entete['taux_tva'] ?? config('facturation.taux_tva_defaut'));
            $totaux = $this->calculerTotaux($lignes, $tvaActive, $tauxTva);

            if ($factureId) {
                $facture = Facture::parSociete($societeId)->findOrFail($factureId);
                if (! $facture->estModifiable()) {
                    throw new InvalidArgumentException('Seules les factures en brouillon sont modifiables.');
                }
            } else {
                $facture = new Facture(['societe_id' => $societeId]);
                $facture->numero = $this->genererNumero($societeId, $type);
                $facture->cree_par = Auth::id();
            }

            $this->verifierTiersType($societeId, (int) $entete['tiers_id'], $type);

            $facture->fill(array_merge($entete, $totaux, [
                'statut' => Facture::STATUT_BROUILLON,
                'tva_active' => $tvaActive,
                'taux_tva' => $tauxTva,
            ]));
            $facture->save();

            $facture->lignes()->delete();
            foreach ($lignes as $i => $l) {
                $q = (float) ($l['quantite'] ?? 1);
                $pu = (float) ($l['prix_unitaire'] ?? 0);
                FactureLigne::create([
                    'facture_id' => $facture->id,
                    'produit_id' => $l['produit_id'] ?? null,
                    'ordre' => $i + 1,
                    'rubrique' => $l['rubrique'] ?? null,
                    'libelle' => $l['libelle'],
                    'quantite' => $q,
                    'prix_unitaire' => $pu,
                    'montant_ht' => round($q * $pu, 2),
                    'compte_comptable' => $l['compte_comptable'] ?? null,
                ]);
            }

            $this->audit->log(
                $factureId ? 'modification' : 'creation',
                'facture',
                $facture->id,
                $facture->numero,
                ($factureId ? 'Modification' : 'Création')." facture {$facture->numero}",
                ['type' => $type, 'montant_ttc' => $facture->montant_ttc],
                $societeId
            );

            return $facture->fresh(['lignes', 'tiers']);
        });
    }

    public function valider(int $societeId, int $factureId): Facture
    {
        return DB::transaction(function () use ($societeId, $factureId) {
            $facture = Facture::parSociete($societeId)->with('lignes', 'tiers')->findOrFail($factureId);

            if ($facture->statut !== Facture::STATUT_BROUILLON) {
                throw new InvalidArgumentException('Seules les factures en brouillon peuvent être validées.');
            }

            if ($facture->lignes->isEmpty()) {
                throw new InvalidArgumentException('La facture doit contenir au moins une ligne.');
            }

            $result = $this->comptable->ecritureValidationFacture($facture);

            $facture->update([
                'statut' => Facture::STATUT_VALIDEE,
                'ecriture_validation_id' => $result['ecriture']->id,
                'valide_par' => Auth::id(),
                'valide_le' => now(),
            ]);

            $this->audit->log('validation', 'facture', $facture->id, $facture->numero, "Validation facture {$facture->numero}", null, $societeId);

            return $facture->fresh(['lignes', 'tiers', 'ecritureValidation']);
        });
    }

    public function annuler(int $societeId, int $factureId, ?string $motif = null): Facture
    {
        $facture = Facture::parSociete($societeId)->findOrFail($factureId);

        if ($facture->statut === Facture::STATUT_PAYEE) {
            throw new InvalidArgumentException('Impossible d\'annuler une facture payée.');
        }

        $facture->update(['statut' => Facture::STATUT_ANNULEE, 'notes' => trim(($facture->notes ?? '')."\nAnnulée: ".($motif ?? ''))]);
        $this->audit->log('annulation', 'facture', $facture->id, $facture->numero, "Annulation {$facture->numero}", ['motif' => $motif], $societeId);

        return $facture;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function echeancierClients(int $societeId, ?string $dateRef = null): array
    {
        $ref = $dateRef ? Carbon::parse($dateRef) : now();

        return Facture::parSociete($societeId)
            ->whereIn('type_document', [Facture::TYPE_VENTE_CLIENT])
            ->where('statut', Facture::STATUT_VALIDEE)
            ->with('tiers:id,code,nom')
            ->orderBy('date_echeance')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'numero' => $f->numero,
                'tiers' => $f->tiers?->nom,
                'date_echeance' => $f->date_echeance?->format('Y-m-d'),
                'montant_ttc' => (float) $f->montant_ttc,
                'devise' => $f->devise,
                'retard_jours' => $f->date_echeance ? max(0, $ref->diffInDays($f->date_echeance, false) * -1) : 0,
                'en_retard' => $f->date_echeance && $f->date_echeance->lt($ref),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function echeancierFournisseurs(int $societeId, ?string $dateRef = null): array
    {
        $ref = $dateRef ? Carbon::parse($dateRef) : now();

        return Facture::parSociete($societeId)
            ->where('type_document', Facture::TYPE_ACHAT_FOURNISSEUR)
            ->where('statut', Facture::STATUT_VALIDEE)
            ->with('tiers:id,code,nom')
            ->orderBy('date_echeance')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'numero' => $f->numero,
                'tiers' => $f->tiers?->nom,
                'date_echeance' => $f->date_echeance?->format('Y-m-d'),
                'montant_ttc' => (float) $f->montant_ttc,
                'devise' => $f->devise,
                'retard_jours' => $f->date_echeance ? max(0, $ref->diffInDays($f->date_echeance, false) * -1) : 0,
                'en_retard' => $f->date_echeance && $f->date_echeance->lt($ref),
            ])
            ->all();
    }

    protected function verifierTiersType(int $societeId, int $tiersId, string $typeDocument): void
    {
        $tiers = Tiers::where('societe_id', $societeId)->findOrFail($tiersId);
        $clientTypes = [Facture::TYPE_VENTE_CLIENT, Facture::TYPE_AVOIR_CLIENT];
        $fournisseurTypes = [Facture::TYPE_ACHAT_FOURNISSEUR, Facture::TYPE_AVOIR_FOURNISSEUR];

        if (in_array($typeDocument, $clientTypes, true) && ! in_array($tiers->type, ['client', 'client_fournisseur'], true)) {
            throw new InvalidArgumentException('Le tiers sélectionné n\'est pas un client.');
        }
        if (in_array($typeDocument, $fournisseurTypes, true) && ! in_array($tiers->type, ['fournisseur', 'client_fournisseur'], true)) {
            throw new InvalidArgumentException('Le tiers sélectionné n\'est pas un fournisseur.');
        }
    }
}
