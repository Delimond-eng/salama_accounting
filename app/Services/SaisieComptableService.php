<?php

namespace App\Services;

use App\Models\Ecriture;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\LigneEcriture;
use App\Models\PlanComptable;
use App\Models\Societe;
use App\Models\TauxChange;
use App\Models\Tiers;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaisieComptableService
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {
    }
    public const PAGES = [
        'nouvelle' => ['code' => null, 'type' => null, 'title' => 'Nouvelle écriture', 'icon' => 'ti-file-plus'],
        'achats' => ['code' => 'HA', 'type' => 'achats', 'title' => 'Journal des achats', 'icon' => 'ti-shopping-cart'],
        'ventes' => ['code' => 'VT', 'type' => 'ventes', 'title' => 'Journal des ventes', 'icon' => 'ti-receipt'],
        'banque' => ['code' => 'BQ', 'type' => 'banque', 'title' => 'Journal de banque', 'icon' => 'ti-building-bank'],
        'caisse' => ['code' => 'CA', 'type' => 'caisse', 'title' => 'Journal de caisse', 'icon' => 'ti-cash'],
        'od' => ['code' => 'OD', 'type' => 'operations_diverses', 'title' => 'Opérations diverses', 'icon' => 'ti-adjustments'],
        'devises' => ['code' => null, 'type' => null, 'title' => 'Écritures en devises', 'icon' => 'ti-currency-dollar', 'multi_devise' => true],
    ];

    public function pageMeta(string $page): array
    {
        if (! isset(self::PAGES[$page])) {
            abort(404);
        }

        return array_merge(['page' => $page], self::PAGES[$page]);
    }

    public function resolveJournal(int $societeId, string $page, ?int $journalId = null): ?Journal
    {
        if ($journalId) {
            return Journal::where('societe_id', $societeId)->where('actif', true)->find($journalId);
        }

        $meta = self::PAGES[$page] ?? null;
        if (! $meta || empty($meta['code'])) {
            return null;
        }

        return Journal::where('societe_id', $societeId)
            ->where('code', $meta['code'])
            ->where('actif', true)
            ->first();
    }

    public function exerciceCourant(int $societeId): ?Exercice
    {
        return Exercice::where('societe_id', $societeId)->where('est_courant', true)->first();
    }

    public function genererNumeroPiece(Journal $journal, Carbon $date): string
    {
        $num = (int) $journal->prochain_numero;
        $padding = max(1, (int) $journal->padding_numero);
        $seq = str_pad((string) $num, $padding, '0', STR_PAD_LEFT);
        $prefix = $journal->prefixe_piece ?: ($journal->code.'-');

        $piece = match ($journal->format_numerotation) {
            'mensuel' => $prefix.$date->format('Y-m').'-'.$seq,
            'continu' => $prefix.$seq,
            default => $prefix.$date->format('Y').'-'.$seq,
        };

        $journal->increment('prochain_numero');

        return $piece;
    }

    public function tauxPourDevise(int $societeId, string $devise, string $date): float
    {
        if (strlen($devise) !== 3) {
            return 1.0;
        }

        $taux = TauxChange::where('societe_id', $societeId)
            ->where('devise_code', strtoupper($devise))
            ->where('date_taux', '<=', $date)
            ->orderByDesc('date_taux')
            ->value('taux');

        return $taux ? (float) $taux : 1.0;
    }

    public function resolveCompte(int $societeId, string $numCompte): PlanComptable
    {
        $compte = PlanComptable::query()
            ->parSociete($societeId)
            ->where('num_compte', $numCompte)
            ->where('actif', true)
            ->first();

        if (! $compte) {
            throw new InvalidArgumentException("Compte {$numCompte} introuvable ou inactif.");
        }

        return $compte;
    }

    /** @return array{debit: float, credit: float} */
    public function calculerTotaux(array $lignes): array
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($lignes as $l) {
            $debit += (float) ($l['debit'] ?? 0);
            $credit += (float) ($l['credit'] ?? 0);
        }

        return ['debit' => round($debit, 2), 'credit' => round($credit, 2)];
    }

    public function verifierEquilibre(array $lignes): void
    {
        $totaux = $this->calculerTotaux($lignes);
        if ($totaux['debit'] <= 0 && $totaux['credit'] <= 0) {
            throw new InvalidArgumentException('L\'écriture doit contenir au moins un montant.');
        }
        if (abs($totaux['debit'] - $totaux['credit']) >= 0.01) {
            throw new InvalidArgumentException(
                sprintf('Écriture déséquilibrée : débit %s ≠ crédit %s.', number_format($totaux['debit'], 2), number_format($totaux['credit'], 2))
            );
        }
    }

    public function suggestTemplate(Journal $journal, ?Tiers $tiers = null): array
    {
        $contrepartie = $journal->compte_contrepartie;
        $libelle = 'Écriture '.$journal->libelle;

        return match ($journal->type) {
            'achats' => [
                ['num_compte' => '601100', 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => null],
                ['num_compte' => $contrepartie ?: '401000', 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => $tiers?->id],
            ],
            'ventes' => [
                ['num_compte' => $contrepartie ?: '411000', 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => $tiers?->id],
                ['num_compte' => '701100', 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => null],
            ],
            'banque', 'caisse' => [
                ['num_compte' => $contrepartie ?: ($journal->type === 'caisse' ? '571000' : '521000'), 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => null],
                ['num_compte' => '658000', 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => null],
            ],
            default => [
                ['num_compte' => '', 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => null],
                ['num_compte' => '', 'libelle' => $libelle, 'debit' => 0, 'credit' => 0, 'tiers_id' => null],
            ],
        };
    }

    /**
     * @return array{ecriture: Ecriture, warnings: array<int, string>}
     */
    public function enregistrer(
        int $societeId,
        array $entete,
        array $lignes,
        bool $valider = false,
        ?int $ecritureId = null
    ): array {
        return DB::transaction(function () use ($societeId, $entete, $lignes, $valider, $ecritureId) {
            $warnings = $this->avertissementsSensInhabituel($lignes);
            $societe = Societe::findOrFail($societeId);
            $exercice = Exercice::where('societe_id', $societeId)->findOrFail($entete['exercice_id']);
            $journal = Journal::where('societe_id', $societeId)->findOrFail($entete['journal_id']);
            $date = Carbon::parse($entete['date_ecriture']);

            if (! $exercice->accepteEcritures($entete['type_ecriture'] ?? 'normale')) {
                throw new InvalidArgumentException('L\'exercice n\'accepte pas de nouvelles écritures.');
            }

            if ($date->lt($exercice->date_debut) || $date->gt($exercice->date_fin)) {
                throw new InvalidArgumentException('La date est hors de la période de l\'exercice.');
            }

            $this->verifierEquilibre($lignes);
            $this->validerLignesMetier($societeId, $journal, $lignes);

            $totaux = $this->calculerTotaux($lignes);
            $devise = strtoupper($entete['devise'] ?? $societe->devise_principale ?? 'CDF');
            $taux = (float) ($entete['taux_change'] ?? $this->tauxPourDevise($societeId, $devise, $date->toDateString()));

            if ($ecritureId) {
                $ecriture = Ecriture::where('societe_id', $societeId)->findOrFail($ecritureId);
                if (! $ecriture->estModifiable()) {
                    throw new InvalidArgumentException('Seules les écritures en brouillon sont modifiables.');
                }
            } else {
                $ecriture = new Ecriture(['societe_id' => $societeId]);
                $ecriture->num_piece = $this->genererNumeroPiece($journal, $date);
                $ecriture->cree_par = Auth::id();
            }

            $ecriture->fill([
                'exercice_id' => $exercice->id,
                'journal_id' => $journal->id,
                'date_ecriture' => $date,
                'date_piece' => $entete['date_piece'] ?? null,
                'date_valeur' => $entete['date_valeur'] ?? null,
                'date_echeance' => $entete['date_echeance'] ?? null,
                'libelle' => $entete['libelle'],
                'type_ecriture' => $entete['type_ecriture'] ?? 'normale',
                'reference_externe' => $entete['reference_externe'] ?? null,
                'reference_facture' => $entete['reference_facture'] ?? null,
                'total_debit' => $totaux['debit'],
                'total_credit' => $totaux['credit'],
                'devise' => $devise,
                'taux_change' => $taux,
                'notes' => $entete['notes'] ?? null,
                'modifie_par' => Auth::id(),
                'statut' => $valider ? 'validee' : 'brouillon',
            ]);

            if ($valider) {
                $ecriture->valide_par = Auth::id();
                $ecriture->valide_le = now();
            }

            $wasNew = ! $ecriture->exists;
            $ecriture->save();

            if ($wasNew) {
                $this->auditLog->logEcriture('creation', $ecriture);
            } else {
                $this->auditLog->logEcriture('modification', $ecriture);
            }
            if ($valider) {
                $this->auditLog->logEcriture('validation', $ecriture);
            }

            $ecriture->lignes()->delete();
            foreach ($lignes as $i => $ligneData) {
                $compte = $this->resolveCompte($societeId, $ligneData['num_compte']);
                $montantDevise = isset($ligneData['montant_devise']) ? (float) $ligneData['montant_devise'] : null;

                LigneEcriture::create([
                    'ecriture_id' => $ecriture->id,
                    'societe_id' => $societeId,
                    'exercice_id' => $exercice->id,
                    'journal_id' => $journal->id,
                    'num_compte' => $compte->num_compte,
                    'compte_id' => $compte->id,
                    'tiers_id' => $ligneData['tiers_id'] ?? null,
                    'date_ecriture' => $date,
                    'libelle' => $ligneData['libelle'] ?? $entete['libelle'],
                    'debit' => (float) ($ligneData['debit'] ?? 0),
                    'credit' => (float) ($ligneData['credit'] ?? 0),
                    'devise' => $ligneData['devise'] ?? $devise,
                    'montant_devise' => $montantDevise,
                    'taux_change' => (float) ($ligneData['taux_change'] ?? $taux),
                    'ordre' => $i + 1,
                    'reference_ligne' => $ligneData['reference_ligne'] ?? null,
                ]);
            }

            return [
                'ecriture' => $ecriture->fresh(['lignes', 'journal']),
                'warnings' => $warnings,
            ];
        });
    }

    /**
     * Avertissements non bloquants sur le sens des mouvements (classes 6 et 7).
     *
     * @return array<int, string>
     */
    public function avertissementsSensInhabituel(array $lignes): array
    {
        $warnings = [];

        foreach ($lignes as $i => $ligne) {
            $num = preg_replace('/\s+/', '', (string) ($ligne['num_compte'] ?? ''));
            if ($num === '') {
                continue;
            }
            $classe = (int) substr($num, 0, 1);
            $debit = (float) ($ligne['debit'] ?? 0);
            $credit = (float) ($ligne['credit'] ?? 0);

            if ($classe === 6 && $credit > 0 && $debit <= 0) {
                $warnings[] = 'Ligne '.($i + 1)." ({$num}) : ⚠️ Sens inhabituel pour ce type de compte.";
            }
            if ($classe === 7 && $debit > 0 && $credit <= 0) {
                $warnings[] = 'Ligne '.($i + 1)." ({$num}) : ⚠️ Sens inhabituel pour ce type de compte.";
            }
        }

        return $warnings;
    }

    protected function validerLignesMetier(int $societeId, Journal $journal, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            $debit = (float) ($ligne['debit'] ?? 0);
            $credit = (float) ($ligne['credit'] ?? 0);
            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('Une ligne ne peut pas avoir débit et crédit simultanément.');
            }
            if ($debit <= 0 && $credit <= 0) {
                throw new InvalidArgumentException('Chaque ligne doit avoir un débit ou un crédit.');
            }

            $compte = $this->resolveCompte($societeId, $ligne['num_compte']);
            if ($compte->est_compte_tiers || $journal->saisie_tiers_obligatoire) {
                if (empty($ligne['tiers_id']) && in_array((int) substr($compte->num_compte, 0, 1), [4], true)) {
                    throw new InvalidArgumentException("Le compte {$compte->num_compte} exige un tiers.");
                }
            }
        }
    }

    public function validerEcriture(int $societeId, int $ecritureId): Ecriture
    {
        return DB::transaction(function () use ($societeId, $ecritureId) {
            $ecriture = Ecriture::with('lignes')->where('societe_id', $societeId)->findOrFail($ecritureId);
            if ($ecriture->statut !== 'brouillon') {
                throw new InvalidArgumentException('Écriture déjà validée ou extournée.');
            }
            if (! $ecriture->estEquilibree()) {
                throw new InvalidArgumentException('Écriture déséquilibrée.');
            }

            $ecriture->update([
                'statut' => 'validee',
                'valide_par' => Auth::id(),
                'valide_le' => now(),
            ]);

            $this->auditLog->logEcriture('validation', $ecriture->fresh());

            return $ecriture->fresh(['lignes', 'journal']);
        });
    }

    public function supprimerBrouillon(int $societeId, int $ecritureId): void
    {
        $ecriture = Ecriture::where('societe_id', $societeId)->findOrFail($ecritureId);
        if (! $ecriture->estModifiable()) {
            throw new InvalidArgumentException('Impossible de supprimer une écriture validée.');
        }
        $this->auditLog->logEcriture('suppression', $ecriture);
        $ecriture->delete();
    }

    /** @return Collection<int, Ecriture> */
    public function importerReleveBancaire(int $societeId, int $journalId, int $exerciceId, array $mouvements): Collection
    {
        $journal = Journal::where('societe_id', $societeId)->findOrFail($journalId);
        if ($journal->type !== 'banque') {
            throw new InvalidArgumentException('L\'import relevé est réservé au journal de banque.');
        }

        $creees = collect();
        foreach ($mouvements as $mvt) {
            $montant = abs((float) ($mvt['montant'] ?? 0));
            if ($montant <= 0) {
                continue;
            }

            $estDebit = ((float) ($mvt['montant'] ?? 0)) > 0;
            $contrepartie = $journal->compte_contrepartie ?: '521000';
            $compteCharge = '658000';

            $lignes = $estDebit
                ? [
                    ['num_compte' => $contrepartie, 'libelle' => $mvt['libelle'], 'debit' => $montant, 'credit' => 0],
                    ['num_compte' => $compteCharge, 'libelle' => $mvt['libelle'], 'debit' => 0, 'credit' => $montant],
                ]
                : [
                    ['num_compte' => $compteCharge, 'libelle' => $mvt['libelle'], 'debit' => $montant, 'credit' => 0],
                    ['num_compte' => $contrepartie, 'libelle' => $mvt['libelle'], 'debit' => 0, 'credit' => $montant],
                ];

            $result = $this->enregistrer($societeId, [
                'exercice_id' => $exerciceId,
                'journal_id' => $journalId,
                'date_ecriture' => $mvt['date'],
                'date_valeur' => $mvt['date'],
                'libelle' => $mvt['libelle'],
                'reference_externe' => $mvt['reference'] ?? null,
                'type_ecriture' => 'normale',
            ], $lignes, valider: false);
            $creees->push($result['ecriture']);
        }

        return $creees;
    }

    public function parseCsvReleve(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (count($lines) < 2) {
            throw new InvalidArgumentException('Fichier CSV vide ou invalide.');
        }

        $sep = str_contains($lines[0], ';') ? ';' : ',';
        $headers = array_map('strtolower', array_map('trim', str_getcsv($lines[0], $sep)));
        $mouvements = [];

        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') {
                continue;
            }
            $cols = str_getcsv($lines[$i], $sep);
            $row = [];
            foreach ($headers as $idx => $h) {
                $row[$h] = $cols[$idx] ?? '';
            }

            $date = $row['date'] ?? $row['date_operation'] ?? $row['date_valeur'] ?? null;
            $libelle = $row['libelle'] ?? $row['description'] ?? $row['label'] ?? 'Mouvement importé';
            $montant = $row['montant'] ?? $row['amount'] ?? null;
            if ($montant === null && isset($row['debit'], $row['credit'])) {
                $d = (float) str_replace([' ', ','], ['', '.'], $row['debit']);
                $c = (float) str_replace([' ', ','], ['', '.'], $row['credit']);
                $montant = $d > 0 ? $d : -$c;
            }

            if (! $date || $montant === null || $montant === '') {
                continue;
            }

            $mouvements[] = [
                'date' => Carbon::parse($date)->toDateString(),
                'libelle' => $libelle,
                'montant' => (float) str_replace([' ', ','], ['', '.'], (string) $montant),
                'reference' => $row['reference'] ?? $row['ref'] ?? null,
            ];
        }

        if (empty($mouvements)) {
            throw new InvalidArgumentException('Aucun mouvement valide trouvé dans le fichier.');
        }

        return $mouvements;
    }
}
