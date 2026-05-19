<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Journal;
use App\Models\Paiement;
use App\Models\Tiers;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Génération d'écritures SYSCOHADA explicites — sans compensation automatique.
 */
class FacturationComptableService
{
    public function __construct(
        protected SaisieComptableService $saisie,
        protected AuditLogService $audit
    ) {}

    public function compte(string $key): string
    {
        return config("facturation.comptes.{$key}");
    }

    public function resolveJournal(int $societeId, string $code): Journal
    {
        $journal = Journal::where('societe_id', $societeId)
            ->where('code', $code)
            ->where('actif', true)
            ->first();

        if (! $journal) {
            throw new InvalidArgumentException("Journal {$code} introuvable pour cette société.");
        }

        return $journal;
    }

    public function compteTiers(Facture $facture): string
    {
        $tiers = $facture->tiers;
        if ($tiers?->num_compte_collectif) {
            return $tiers->num_compte_collectif;
        }

        return $facture->estClient()
            ? $this->compte('client')
            : $this->compte('fournisseur');
    }

    /**
     * @return array{ecriture: \App\Models\Ecriture}
     */
    public function ecritureValidationFacture(Facture $facture): array
    {
        $facture->load(['lignes', 'tiers', 'societe']);
        $montant = (float) $facture->montant_ht;
        $tva = (float) $facture->montant_tva;
        $ttc = (float) $facture->montant_ttc;
        $tiersId = $facture->tiers_id;
        $libelle = $facture->objet ?: ('Facture '.$facture->numero);
        $compteTiers = $this->compteTiers($facture);
        $date = $facture->date_facture->format('Y-m-d');
        $exercice = $this->saisie->exerciceCourant($facture->societe_id);

        if (! $exercice) {
            throw new InvalidArgumentException('Aucun exercice courant.');
        }

        $lignes = match ($facture->type_document) {
            Facture::TYPE_VENTE_CLIENT => $this->lignesVenteClient($facture, $compteTiers, $montant, $tva, $ttc, $tiersId, $libelle),
            Facture::TYPE_ACHAT_FOURNISSEUR => $this->lignesAchatFournisseur($facture, $compteTiers, $montant, $tva, $ttc, $tiersId, $libelle),
            Facture::TYPE_AVOIR_CLIENT => $this->lignesAvoirClient($facture, $compteTiers, $montant, $tva, $ttc, $tiersId, $libelle),
            Facture::TYPE_AVOIR_FOURNISSEUR => $this->lignesAvoirFournisseur($facture, $compteTiers, $montant, $tva, $ttc, $tiersId, $libelle),
            default => throw new InvalidArgumentException('Type de document non pris en charge.'),
        };

        $codeJournal = match ($facture->type_document) {
            Facture::TYPE_VENTE_CLIENT, Facture::TYPE_AVOIR_CLIENT => config('facturation.journaux.vente_client'),
            default => config('facturation.journaux.achat_fournisseur'),
        };

        $journal = $this->resolveJournal($facture->societe_id, $codeJournal);

        $result = $this->saisie->enregistrer(
            $facture->societe_id,
            [
                'exercice_id' => $exercice->id,
                'journal_id' => $journal->id,
                'date_ecriture' => $date,
                'date_echeance' => $facture->date_echeance?->format('Y-m-d'),
                'libelle' => $libelle,
                'reference_externe' => $facture->numero,
                'reference_facture' => $facture->numero,
                'devise' => $facture->devise,
                'type_ecriture' => 'normale',
            ],
            $lignes,
            true
        );

        $this->audit->log(
            'validation_comptable',
            'facture',
            $facture->id,
            $facture->numero,
            "Écriture de validation — {$facture->numero}",
            ['ecriture_id' => $result['ecriture']->id],
            $facture->societe_id
        );

        return $result;
    }

    /**
     * @return array{ecriture: \App\Models\Ecriture}
     */
    public function ecriturePaiement(Paiement $paiement, Facture $facture): array
    {
        $facture->load('tiers');
        $montant = (float) $paiement->montant;
        $compteTiers = $this->compteTiers($facture);
        $compteTreso = $paiement->compte_tresorerie;
        $libelle = 'Paiement '.$paiement->numero.' — '.$facture->numero;
        $date = $paiement->date_paiement->format('Y-m-d');
        $exercice = $this->saisie->exerciceCourant($paiement->societe_id);

        if (! $exercice) {
            throw new InvalidArgumentException('Aucun exercice courant.');
        }

        $codeJournal = $paiement->methode === 'caisse'
            ? config('facturation.journaux.caisse')
            : config('facturation.journaux.banque');

        $journal = $this->resolveJournal($paiement->societe_id, $codeJournal);

        if ($facture->estClient()) {
            $lignes = [
                ['num_compte' => $compteTreso, 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0, 'tiers_id' => null],
                ['num_compte' => $compteTiers, 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant, 'tiers_id' => $facture->tiers_id],
            ];
        } else {
            $lignes = [
                ['num_compte' => $compteTiers, 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0, 'tiers_id' => $facture->tiers_id],
                ['num_compte' => $compteTreso, 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant, 'tiers_id' => null],
            ];
        }

        $result = $this->saisie->enregistrer(
            $paiement->societe_id,
            [
                'exercice_id' => $exercice->id,
                'journal_id' => $journal->id,
                'date_ecriture' => $date,
                'libelle' => $libelle,
                'reference_externe' => $paiement->numero,
                'reference_facture' => $facture->numero,
                'devise' => $paiement->devise,
            ],
            $lignes,
            true
        );

        $this->audit->log(
            'paiement_comptable',
            'paiement',
            $paiement->id,
            $paiement->numero,
            "Écriture de paiement — {$paiement->numero}",
            ['ecriture_id' => $result['ecriture']->id, 'facture_id' => $facture->id],
            $paiement->societe_id
        );

        return $result;
    }

    /**
     * @return array{ecriture: \App\Models\Ecriture}
     */
    public function ecritureDemandeFonds(
        int $societeId,
        string $compteDebit,
        string $compteCredit,
        float $montant,
        string $libelle,
        string $date,
        string $devise = 'CDF',
        ?int $journalId = null
    ): array {
        $exercice = $this->saisie->exerciceCourant($societeId);
        if (! $exercice) {
            throw new InvalidArgumentException('Aucun exercice courant.');
        }

        $journal = $journalId
            ? Journal::where('societe_id', $societeId)->findOrFail($journalId)
            : $this->resolveJournal($societeId, config('facturation.journaux.od'));

        $lignes = [
            ['num_compte' => $compteDebit, 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0, 'tiers_id' => null],
            ['num_compte' => $compteCredit, 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant, 'tiers_id' => null],
        ];

        return $this->saisie->enregistrer(
            $societeId,
            [
                'exercice_id' => $exercice->id,
                'journal_id' => $journal->id,
                'date_ecriture' => $date,
                'libelle' => $libelle,
                'devise' => $devise,
            ],
            $lignes,
            true
        );
    }

    protected function lignesVenteClient(
        Facture $facture,
        string $compteTiers,
        float $ht,
        float $tva,
        float $ttc,
        int $tiersId,
        string $libelle
    ): array {
        $lignes = [
            ['num_compte' => $compteTiers, 'libelle' => $libelle, 'debit' => $ttc, 'credit' => 0, 'tiers_id' => $tiersId],
        ];

        if ($tva > 0) {
            $lignes[] = ['num_compte' => $this->compte('tva_collectee'), 'libelle' => $libelle, 'debit' => 0, 'credit' => $tva, 'tiers_id' => null];
        }

        $lignes[] = ['num_compte' => $this->compteVenteLigne($facture), 'libelle' => $libelle, 'debit' => 0, 'credit' => $ht, 'tiers_id' => null];

        return $lignes;
    }

    protected function lignesAchatFournisseur(
        Facture $facture,
        string $compteTiers,
        float $ht,
        float $tva,
        float $ttc,
        int $tiersId,
        string $libelle
    ): array {
        $lignes = [
            ['num_compte' => $this->compteAchatLigne($facture), 'libelle' => $libelle, 'debit' => $ht, 'credit' => 0, 'tiers_id' => null],
        ];

        if ($tva > 0) {
            $lignes[] = ['num_compte' => $this->compte('tva_deductible'), 'libelle' => $libelle, 'debit' => $tva, 'credit' => 0, 'tiers_id' => null];
        }

        $lignes[] = ['num_compte' => $compteTiers, 'libelle' => $libelle, 'debit' => 0, 'credit' => $ttc, 'tiers_id' => $tiersId];

        return $lignes;
    }

    protected function lignesAvoirClient(
        Facture $facture,
        string $compteTiers,
        float $ht,
        float $tva,
        float $ttc,
        int $tiersId,
        string $libelle
    ): array {
        $lignes = [
            ['num_compte' => $this->compteVenteLigne($facture), 'libelle' => $libelle, 'debit' => $ht, 'credit' => 0, 'tiers_id' => null],
        ];

        if ($tva > 0) {
            $lignes[] = ['num_compte' => $this->compte('tva_collectee'), 'libelle' => $libelle, 'debit' => $tva, 'credit' => 0, 'tiers_id' => null];
        }

        $lignes[] = ['num_compte' => $compteTiers, 'libelle' => $libelle, 'debit' => 0, 'credit' => $ttc, 'tiers_id' => $tiersId];

        return $lignes;
    }

    protected function lignesAvoirFournisseur(
        Facture $facture,
        string $compteTiers,
        float $ht,
        float $tva,
        float $ttc,
        int $tiersId,
        string $libelle
    ): array {
        $lignes = [
            ['num_compte' => $compteTiers, 'libelle' => $libelle, 'debit' => $ttc, 'credit' => 0, 'tiers_id' => $tiersId],
        ];

        if ($tva > 0) {
            $lignes[] = ['num_compte' => $this->compte('tva_deductible'), 'libelle' => $libelle, 'debit' => 0, 'credit' => $tva, 'tiers_id' => null];
        }

        $lignes[] = ['num_compte' => $this->compteAchatLigne($facture), 'libelle' => $libelle, 'debit' => 0, 'credit' => $ht, 'tiers_id' => null];

        return $lignes;
    }

    protected function compteVenteLigne(Facture $facture): string
    {
        $ligne = $facture->lignes->first();

        return $ligne?->compte_comptable ?: $this->compte('vente');
    }

    protected function compteAchatLigne(Facture $facture): string
    {
        $ligne = $facture->lignes->first();

        return $ligne?->compte_comptable ?: $this->compte('achat');
    }
}
