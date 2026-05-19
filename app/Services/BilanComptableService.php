<?php

namespace App\Services;

use App\Exceptions\BilanDesequilibreException;
use App\Models\Exercice;
use App\Models\PlanComptable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bilan SYSCOHADA — déterministe, auditable, sans correction automatique.
 *
 * Montants : actif = débit − crédit ; passif = crédit − débit (signe naturel, jamais ABS global).
 * Pipeline : soldes (classes 1–5) → classification fixe → injection 130000 → contrôle TA = TP.
 */
class BilanComptableService
{
    public const SIDE_ACTIF = 'actif';

    public const SIDE_PASSIF = 'passif';

    private const TOLERANCE = 0.02;

    public function __construct(
        protected LivresComptablesService $livres
    ) {}

    // -------------------------------------------------------------------------
    // Extraction des soldes
    // -------------------------------------------------------------------------

    public function getAccountBalances(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine'
    ): Collection {
        $balance = $this->livres->balanceGenerale(
            $societeId,
            $exercice->id,
            $exercice->date_debut->format('Y-m-d'),
            $dateFin,
            $deviseAffichage,
            $modeConversion
        );

        $comptesMeta = PlanComptable::query()
            ->parSociete($societeId)
            ->whereIn('num_compte', collect($balance['lignes'])->pluck('num_compte'))
            ->get()
            ->keyBy('num_compte');

        return collect($balance['lignes'])->mapWithKeys(function ($row) use ($comptesMeta) {
            $num = (string) $row['num_compte'];
            $meta = $comptesMeta->get($num);
            $debit = round((float) ($row['solde_fin_debiteur'] ?? 0), 2);
            $credit = round((float) ($row['solde_fin_crediteur'] ?? 0), 2);

            return [$num => [
                'compte_id' => $meta?->id,
                'num_compte' => $num,
                'libelle' => $row['libelle'] ?? $meta?->libelle ?? $num,
                'classe' => (int) ($meta?->classe ?? (int) substr($num, 0, 1)),
                'type_compte' => $meta?->type_compte ?? 'bilan',
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($debit - $credit, 2),
            ]];
        })->filter(function (array $a): bool {
            $classe = (int) $a['classe'];

            if ($classe < 1 || $classe > 5) {
                return false;
            }

            if (($a['type_compte'] ?? 'bilan') === 'gestion') {
                return false;
            }

            return abs($a['balance']) >= 0.0001 || $a['debit'] > 0 || $a['credit'] > 0;
        });
    }

    /**
     * Résultat net = Σ crédits − Σ débits (classes 6 et 7 uniquement).
     */
    public function resultatNetExercice(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine'
    ): float {
        $rows = DB::table('lignes_ecritures as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->where('l.societe_id', $societeId)
            ->where('l.exercice_id', $exercice->id)
            ->where('e.statut', 'validee')
            ->whereNull('e.deleted_at')
            ->whereBetween('e.date_ecriture', [$exercice->date_debut->format('Y-m-d'), $dateFin])
            ->where(function ($q): void {
                $q->where('l.num_compte', 'like', '6%')
                    ->orWhere('l.num_compte', 'like', '7%');
            })
            ->selectRaw('COALESCE(SUM(l.debit), 0) as d, COALESCE(SUM(l.credit), 0) as c')
            ->first();

        return round((float) ($rows->c ?? 0) - (float) ($rows->d ?? 0), 2);
    }

    // -------------------------------------------------------------------------
    // Classification fixe (numéro de compte — aucune logique économique)
    // -------------------------------------------------------------------------

    /**
     * @return array{side: string, section: string, nature?: string}|null null = compte non classable → erreur bloquante
     */
    public function classifyAccount(array $account): ?array
    {
        $num = (string) $account['num_compte'];
        $classe = (int) $account['classe'];

        if ($classe < 1 || $classe > 5) {
            return null;
        }

        // Règles fixes prioritaires (SYSCOHADA)
        if (str_starts_with($num, '101')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'capital', 'nature' => 'capitaux_propres'];
        }

        if (str_starts_with($num, '130')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'report_resultat', 'nature' => 'resultat'];
        }

        if (str_starts_with($num, '401')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'passif_circulant', 'nature' => 'fournisseur'];
        }

        if (str_starts_with($num, '411')) {
            return ['side' => self::SIDE_ACTIF, 'section' => 'actif_circulant', 'nature' => 'client'];
        }

        if (str_starts_with($num, '521') || str_starts_with($num, '571')) {
            return $this->classifierTresorerie($account);
        }

        return match ($classe) {
            1 => $this->classifierClasse1($num),
            2 => ['side' => self::SIDE_ACTIF, 'section' => 'actif_immobilise'],
            3 => ['side' => self::SIDE_ACTIF, 'section' => 'actif_circulant'],
            4 => $this->classifierClasse4($num),
            5 => ['side' => self::SIDE_ACTIF, 'section' => 'tresorerie_actif', 'nature' => 'tresorerie'],
            default => null,
        };
    }

    /**
     * 521 / 571 : actif par défaut ; passif uniquement si solde strictement négatif (découvert).
     */
    protected function classifierTresorerie(array $account): array
    {
        $balance = (float) $account['balance'];

        if ($balance < 0) {
            return [
                'side' => self::SIDE_PASSIF,
                'section' => 'tresorerie_passif',
                'nature' => 'decouvert_bancaire',
            ];
        }

        return [
            'side' => self::SIDE_ACTIF,
            'section' => 'tresorerie_actif',
            'nature' => 'tresorerie',
        ];
    }

    /**
     * @return array{side: string, section: string, nature?: string}
     */
    protected function classifierClasse1(string $num): array
    {
        $p2 = substr($num, 0, 2);

        if (in_array($p2, ['16', '17', '18', '19'], true)) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'emprunts_associes', 'nature' => 'dette_financiere'];
        }

        if (str_starts_with($num, '102') || str_starts_with($num, '103') || str_starts_with($num, '109')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'capital', 'nature' => 'capitaux_propres'];
        }

        if (str_starts_with($num, '104') || str_starts_with($num, '106') || $p2 === '11') {
            return ['side' => self::SIDE_PASSIF, 'section' => 'reserves', 'nature' => 'capitaux_propres'];
        }

        if (str_starts_with($num, '12')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'report_resultat', 'nature' => 'report'];
        }

        if (str_starts_with($num, '13')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'report_resultat', 'nature' => 'resultat'];
        }

        return ['side' => self::SIDE_PASSIF, 'section' => 'report_resultat', 'nature' => 'capitaux_propres'];
    }

    /**
     * @return array{side: string, section: string, nature?: string}
     */
    protected function classifierClasse4(string $num): array
    {
        if (str_starts_with($num, '402') || str_starts_with($num, '403')
            || str_starts_with($num, '404') || str_starts_with($num, '405')
            || (str_starts_with($num, '40') && ! str_starts_with($num, '409'))) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'passif_circulant', 'nature' => 'fournisseur'];
        }

        if (str_starts_with($num, '409')) {
            return ['side' => self::SIDE_ACTIF, 'section' => 'actif_circulant', 'nature' => 'avance_fournisseur'];
        }

        if (str_starts_with($num, '419')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'passif_circulant', 'nature' => 'avance_client'];
        }

        if (str_starts_with($num, '41') && ! str_starts_with($num, '419')) {
            return ['side' => self::SIDE_ACTIF, 'section' => 'actif_circulant', 'nature' => 'client'];
        }

        if (str_starts_with($num, '43') || str_starts_with($num, '44')
            || str_starts_with($num, '45') || str_starts_with($num, '46') || str_starts_with($num, '49')) {
            return ['side' => self::SIDE_PASSIF, 'section' => 'passif_circulant'];
        }

        return ['side' => self::SIDE_ACTIF, 'section' => 'actif_circulant', 'nature' => 'creance'];
    }

    /**
     * @return array{actif: array<string, array>, passif: array<string, array>, non_affectes: array<string, array>}
     */
    protected function classerComptes(Collection $soldes): array
    {
        $actif = [];
        $passif = [];
        $nonAffectes = [];

        foreach ($soldes as $num => $compte) {
            $classification = $this->classifyAccount($compte);

            if ($classification === null) {
                $nonAffectes[$num] = $compte;
                continue;
            }

            $entry = array_merge($compte, $classification);

            if ($classification['side'] === self::SIDE_ACTIF) {
                $actif[$num] = $entry;
            } else {
                $passif[$num] = $entry;
            }
        }

        return compact('actif', 'passif', 'nonAffectes');
    }

    /**
     * Résultat (6–7) → compte 130000 passif uniquement.
     * Tous les comptes 13* du grand livre sont retirés (pas de double comptage).
     */
    protected function injecterResultatExercice(array $assignations, float $resultatNet): array
    {
        foreach (array_keys($assignations['passif']) as $num) {
            if (str_starts_with((string) $num, '13')) {
                unset($assignations['passif'][$num]);
            }
        }

        if (abs($resultatNet) < 0.01) {
            return $assignations;
        }

        $assignations['passif']['130000'] = [
            'compte_id' => null,
            'num_compte' => '130000',
            'libelle' => 'Résultat net de l\'exercice (classes 6–7)',
            'classe' => 1,
            'debit' => $resultatNet < 0 ? abs($resultatNet) : 0.0,
            'credit' => $resultatNet > 0 ? $resultatNet : 0.0,
            'balance' => $resultatNet,
            'side' => self::SIDE_PASSIF,
            'section' => 'report_resultat',
            'nature' => 'resultat_exercice',
            'origine' => 'classes_6_7',
        ];

        return $assignations;
    }

    // -------------------------------------------------------------------------
    // Montants bilan — soldes naturels signés (unique fonction de calcul)
    // -------------------------------------------------------------------------

    /**
     * Actif : débit − crédit. Passif : crédit − débit. Aucun ABS, aucune compensation.
     */
    protected function montantBilan(array $entry): float
    {
        $debit = (float) ($entry['debit'] ?? 0);
        $credit = (float) ($entry['credit'] ?? 0);

        if (($entry['side'] ?? '') === self::SIDE_PASSIF) {
            return round($credit - $debit, 2);
        }

        return round($debit - $credit, 2);
    }

    /**
     * @return array{total_actif: float, total_passif: float, ecart: float}
     */
    protected function calculerTotaux(array $assignations): array
    {
        $totalActif = round(collect($assignations['actif'])->sum(fn ($e) => $this->montantBilan($e)), 2);
        $totalPassif = round(collect($assignations['passif'])->sum(fn ($e) => $this->montantBilan($e)), 2);

        return [
            'total_actif' => $totalActif,
            'total_passif' => $totalPassif,
            'ecart' => round($totalActif - $totalPassif, 2),
        ];
    }

    protected function totalCapitauxPropres(array $passif): float
    {
        return round(collect($passif)->sum(function (array $entry): float {
            $section = $entry['section'] ?? '';

            return in_array($section, ['capital', 'reserves', 'report_resultat'], true)
                ? $this->montantBilan($entry)
                : 0.0;
        }), 2);
    }

    /**
     * @param  list<string>  $sections
     */
    protected function totalParSections(array $comptes, array $sections): float
    {
        return round(collect($comptes)->sum(function (array $entry) use ($sections): float {
            return in_array($entry['section'] ?? '', $sections, true)
                ? $this->montantBilan($entry)
                : 0.0;
        }), 2);
    }

    protected function libelleLigne(array $item): string
    {
        $num = (string) ($item['num_compte'] ?? '');

        if (str_starts_with($num, '101')) {
            return 'Capital social';
        }

        if (str_starts_with($num, '130000')) {
            return 'Résultat net de l\'exercice';
        }

        $libelle = (string) ($item['libelle'] ?? $num);

        return match ($item['nature'] ?? null) {
            'fournisseur' => 'Fournisseurs — '.$libelle,
            'client' => $libelle,
            'avance_fournisseur' => 'Avances aux fournisseurs — '.$libelle,
            'avance_client' => 'Avances clients reçues — '.$libelle,
            'decouvert_bancaire' => $libelle.' (découvert bancaire)',
            default => $libelle,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function construireColonne(string $colonne, array $comptes): array
    {
        $sectionsConfig = config('bilan_sections.'.$colonne, []);
        $parSection = [];

        foreach ($comptes as $entry) {
            $parSection[$entry['section']][] = $entry;
        }

        $lignes = [];
        $ordre = 0;

        foreach ($sectionsConfig as $sectionKey => $meta) {
            $items = $parSection[$sectionKey] ?? [];

            if ($items === []) {
                continue;
            }

            $lignes[] = [
                'type' => 'titre',
                'libelle' => $meta['titre'] ?? $meta['libelle'],
                'brut_n' => null,
                'amort_n' => null,
                'net_n' => null,
                'balance' => null,
            ];

            usort($items, fn ($a, $b) => strcmp((string) $a['num_compte'], (string) $b['num_compte']));

            foreach ($items as $item) {
                $montant = $this->montantBilan($item);
                if (abs($montant) < 0.01) {
                    continue;
                }

                $lignes[] = [
                    'type' => 'ligne',
                    'libelle' => $this->libelleLigne($item),
                    'num_compte' => $item['num_compte'],
                    'brut_n' => $montant,
                    'amort_n' => 0.0,
                    'net_n' => $montant,
                    'balance' => $montant,
                    'detail' => true,
                ];
            }

            $sousTotal = round(collect($items)->sum(fn ($i) => $this->montantBilan($i)), 2);

            if (abs($sousTotal) < 0.01) {
                continue;
            }

            $lignes[] = [
                'type' => 'total',
                'ref' => strtoupper(substr($sectionKey, 0, 2)).($ordre++),
                'libelle' => 'Total '.($meta['libelle'] ?? $sectionKey),
                'brut_n' => $sousTotal,
                'amort_n' => 0.0,
                'net_n' => $sousTotal,
                'balance' => $sousTotal,
            ];
        }

        $refTotal = $colonne === 'actif' ? 'TA' : 'TP';
        $total = round(collect($comptes)->sum(fn ($i) => $this->montantBilan($i)), 2);

        $lignes[] = [
            'type' => 'total',
            'ref' => $refTotal,
            'libelle' => $colonne === 'actif' ? 'TOTAL ACTIF' : 'TOTAL PASSIF',
            'brut_n' => $total,
            'amort_n' => 0.0,
            'net_n' => $total,
            'balance' => $total,
        ];

        return $lignes;
    }

    // -------------------------------------------------------------------------
    // Contrôles (aucune correction)
    // -------------------------------------------------------------------------

    protected function validerBilan(array $payload, array $assignations, float $resultatNet): void
    {
        $totaux = [
            'total_actif' => $payload['total_actif'],
            'total_passif' => $payload['total_passif'],
            'ecart' => $payload['ecart'],
        ];

        $nonAffectes = $assignations['non_affectes'] ?? [];

        if ($nonAffectes !== []) {
            throw new BilanDesequilibreException(
                $totaux['total_actif'],
                $totaux['total_passif'],
                $payload['total_capitaux_propres'],
                $totaux['ecart'],
                [
                    'type' => 'comptes_non_affectes',
                    'comptes_non_affectes' => $this->listerComptes($nonAffectes),
                    'resultat_exercice' => $resultatNet,
                    'payload' => $payload,
                ]
            );
        }

        if (abs($totaux['ecart']) >= self::TOLERANCE) {
            throw new BilanDesequilibreException(
                $totaux['total_actif'],
                $totaux['total_passif'],
                $payload['total_capitaux_propres'],
                $totaux['ecart'],
                [
                    'type' => 'ecart_bilan',
                    'resultat_exercice' => $resultatNet,
                    'comptes_actif' => $this->listerComptes($assignations['actif']),
                    'comptes_passif' => $this->listerComptes($assignations['passif']),
                    'payload' => $payload,
                ]
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function construirePayload(
        array $assignations,
        array $totaux,
        Exercice $exercice,
        string $dateArrete,
        string $deviseAffichage,
        float $resultatNet,
        int $nbComptesBilan
    ): array {
        $actif = $this->construireColonne('actif', $assignations['actif']);
        $passif = $this->construireColonne('passif', $assignations['passif']);
        $totalCp = $this->totalCapitauxPropres($assignations['passif']);
        $totalDettes = $this->totalParSections($assignations['passif'], [
            'emprunts_associes',
            'passif_circulant',
            'tresorerie_passif',
        ]);

        $equilibre = abs($totaux['ecart']) < self::TOLERANCE;

        return [
            'titre' => 'BILAN — Système normal',
            'date_arrete' => $dateArrete,
            'exercice_n' => $exercice->libelle,
            'devise' => $deviseAffichage,
            'actif' => $actif,
            'passif' => $passif,
            'total_actif' => $totaux['total_actif'],
            'total_passif' => $totaux['total_passif'],
            'total_capitaux_propres' => $totalCp,
            'total_dettes' => $totalDettes,
            'total_passif_et_equity' => $totaux['total_passif'],
            'resultat_exercice' => $resultatNet,
            'equilibre' => $equilibre,
            'ecart' => $totaux['ecart'],
            'validation' => [
                'equilibre' => $equilibre,
                'ecart' => $totaux['ecart'],
                'message' => $equilibre
                    ? 'Bilan équilibré : TOTAL ACTIF = TOTAL PASSIF.'
                    : 'Bilan non équilibré : TOTAL ACTIF ≠ TOTAL PASSIF (soldes naturels signés).',
                'total_actif' => $totaux['total_actif'],
                'total_passif' => $totaux['total_passif'],
                'total_capitaux_propres' => $totalCp,
                'total_dettes' => $totalDettes,
                'comptes_actif' => $this->listerComptes($assignations['actif']),
                'comptes_passif' => $this->listerComptes($assignations['passif']),
            ],
            'pipeline' => [
                'classification' => 'fixe_numero_compte',
                'reporting' => 'soldes_naturels_signes',
                'resultat' => '130000',
                'compensation' => false,
                'nb_comptes_bilan' => $nbComptesBilan,
            ],
        ];
    }

    /**
     * @return list<array{num_compte: string, libelle: string, balance: float, montant: float, section: string}>
     */
    protected function listerComptes(array $bucket): array
    {
        return collect($bucket)
            ->map(fn (array $c) => [
                'num_compte' => (string) ($c['num_compte'] ?? ''),
                'libelle' => (string) ($c['libelle'] ?? ''),
                'balance' => round((float) ($c['balance'] ?? 0), 2),
                'montant' => $this->montantBilan($c),
                'section' => (string) ($c['section'] ?? ''),
            ])
            ->filter(fn (array $c) => abs($c['montant']) >= 0.01)
            ->sortBy('num_compte')
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Point d'entrée
    // -------------------------------------------------------------------------

    public function generer(
        int $societeId,
        Exercice $exercice,
        string $dateArrete,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine'
    ): array {
        $soldes = $this->getAccountBalances($societeId, $exercice, $dateArrete, $deviseAffichage, $modeConversion);
        $resultatNet = $this->resultatNetExercice($societeId, $exercice, $dateArrete, $deviseAffichage, $modeConversion);

        $assignations = $this->classerComptes($soldes);
        $assignations = $this->injecterResultatExercice($assignations, $resultatNet);

        $totaux = $this->calculerTotaux($assignations);
        $payload = $this->construirePayload(
            $assignations,
            $totaux,
            $exercice,
            $dateArrete,
            $deviseAffichage,
            $resultatNet,
            $soldes->count()
        );

        $this->validerBilan($payload, $assignations, $resultatNet);

        return $payload;
    }
}
