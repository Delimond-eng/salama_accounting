<?php

namespace App\Services;

use App\Models\Exercice;
use App\Models\Societe;
use Illuminate\Support\Collection;

class EtatsFinanciersService
{
    public function __construct(
        protected LivresComptablesService $livres,
        protected BilanComptableService $bilanComptable,
        protected FluxTresorerieService $fluxTresorerie
    ) {}

    public function exercicePrecedent(int $societeId, Exercice $courant): ?Exercice
    {
        return Exercice::where('societe_id', $societeId)
            ->where('date_fin', '<', $courant->date_debut)
            ->orderByDesc('date_fin')
            ->first();
    }

    protected function soldesComptes(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        string $scopeDevise = 'consolide'
    ): Collection {
        $balance = $this->livres->balanceGenerale(
            $societeId,
            $exercice->id,
            $exercice->date_debut->format('Y-m-d'),
            $dateFin,
            $deviseAffichage,
            $modeConversion,
            null,
            $scopeDevise
        );

        return collect($balance['lignes'])->mapWithKeys(function ($row) {
            $debit = (float) ($row['solde_fin_debiteur'] ?? 0);
            $credit = (float) ($row['solde_fin_crediteur'] ?? 0);
            $classe = (int) substr((string) $row['num_compte'], 0, 1);

            return [$row['num_compte'] => [
                'num_compte' => $row['num_compte'],
                'libelle' => $row['libelle'],
                'classe' => $classe,
                'debit' => $debit,
                'credit' => $credit,
                'net_actif' => round($debit - $credit, 2),
                'net_passif' => round($credit - $debit, 2),
                'mouvement_debit' => (float) ($row['mouvement_debit'] ?? 0),
                'mouvement_credit' => (float) ($row['mouvement_credit'] ?? 0),
            ]];
        });
    }

    protected function matchPrefix(string $numCompte, array $prefixes, array $exclude = []): bool
    {
        foreach ($exclude as $ex) {
            if (str_starts_with($numCompte, $ex)) {
                return false;
            }
        }
        foreach ($prefixes as $p) {
            if (str_starts_with($numCompte, (string) $p)) {
                return true;
            }
        }

        return false;
    }

    protected function sommePrefixes(
        Collection $soldes,
        array $prefixes,
        string $nature = 'actif',
        array $exclude = [],
        ?array &$comptesUtilises = null,
        ?array $sensComptes = null
    ): float {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if ($comptesUtilises !== null && isset($comptesUtilises[$num])) {
                continue;
            }
            if (! $this->matchPrefix($num, $prefixes, $exclude)) {
                continue;
            }
            if (($s['classe'] ?? 0) === 5) {
                continue;
            }
            $montant = $this->montantComptePourNature($num, $s, $nature, $sensComptes);
            $total += $montant;
            if ($comptesUtilises !== null && $montant != 0) {
                $comptesUtilises[$num] = true;
            }
        }

        return round($total, 2);
    }

    protected function montantComptePourNature(string $numCompte, array $solde, string $nature, ?array $sensComptes = null): float
    {
        if ($sensComptes) {
            foreach ($sensComptes as $prefix => $sens) {
                if (str_starts_with($numCompte, (string) $prefix)) {
                    if ($sens === 'debit') {
                        return max(0, $solde['net_actif']);
                    }
                    if ($sens === 'credit') {
                        return max(0, $solde['net_passif']);
                    }
                }
            }
        }

        $montant = $nature === 'passif' ? $solde['net_passif'] : $solde['net_actif'];

        return max(0, $montant);
    }

    protected function soldeSigneCompte(array $solde, string $mode = 'actif'): float
    {
        return match ($mode) {
            'passif' => round((float) $solde['credit'] - (float) $solde['debit'], 2),
            'passif_odoo' => round((float) $solde['debit'] - (float) $solde['credit'], 2),
            default => round((float) $solde['debit'] - (float) $solde['credit'], 2),
        };
    }

    protected function estCompteClasse5(string $numCompte, array $solde): bool
    {
        return ($solde['classe'] ?? (int) substr($numCompte, 0, 1)) === 5;
    }

    /**
     * Trésorerie actif : comptes classe 5 avec solde débiteur (SUM débit > SUM crédit en clôture).
     */
    protected function tresorerieActifClasse5(
        Collection $soldes,
        ?array &$comptesUtilises = null,
        ?array $prefixes = null,
        array $exclude = [],
        bool $signe = false
    ): float {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if (! $this->estCompteClasse5($num, $s)) {
                continue;
            }
            if ($comptesUtilises !== null && isset($comptesUtilises[$num])) {
                continue;
            }
            if ($prefixes !== null && ! $this->matchPrefix($num, $prefixes, $exclude)) {
                continue;
            }
            $montant = $this->soldeSigneCompte($s, 'actif');
            if (! $signe && $montant <= 0) {
                continue;
            }
            $total += $montant;
            if ($comptesUtilises !== null && abs($montant) >= 0.01) {
                $comptesUtilises[$num] = true;
            }
        }

        return round($total, 2);
    }

    /**
     * Trésorerie passif : comptes classe 5 avec solde créditeur (SUM crédit > SUM débit en clôture).
     */
    protected function tresoreriePassifClasse5(
        Collection $soldes,
        ?array &$comptesUtilises = null,
        ?array $prefixes = null,
        array $exclude = []
    ): float {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if (! $this->estCompteClasse5($num, $s)) {
                continue;
            }
            if ($comptesUtilises !== null && isset($comptesUtilises[$num])) {
                continue;
            }
            if ($prefixes !== null && ! $this->matchPrefix($num, $prefixes, $exclude)) {
                continue;
            }
            if ((float) $s['credit'] <= (float) $s['debit']) {
                continue;
            }
            $total += round((float) $s['credit'] - (float) $s['debit'], 2);
            if ($comptesUtilises !== null) {
                $comptesUtilises[$num] = true;
            }
        }

        return round($total, 2);
    }

    protected function evaluerTotalSomme(string $somme, Collection $soldes, ?array &$comptesUtilises = null): float
    {
        return match ($somme) {
            'lignes_actif_immobilise' => $this->sommeClasses($soldes, [2], 'actif'),
            'lignes_actif_circulant' => round(
                $this->sommeClasses($soldes, [3, 4], 'actif')
                + $this->sommePrefixes($soldes, ['485', '488'], 'actif', [], $comptesUtilises),
                2
            ),
            'lignes_tresorerie_actif' => $this->tresorerieActifClasse5($soldes, $comptesUtilises, null, [], true),
            'lignes_capitaux_propres' => $this->sommePrefixes(
                $soldes,
                ['101', '102', '103', '104', '105', '106', '109', '111', '112', '113', '12', '13', '14', '15'],
                'passif',
                [],
                $comptesUtilises
            ),
            'lignes_dettes_financieres' => $this->sommePrefixes(
                $soldes,
                ['16', '17', '18', '19'],
                'passif',
                [],
                $comptesUtilises
            ),
            'lignes_passif_circulant' => $this->sommePrefixes(
                $soldes,
                ['401', '419', '42', '43', '44', '45', '46', '47', '48', '49', '481', '482', '484'],
                'passif',
                [],
                $comptesUtilises
            ) + $this->sommePrefixes(
                $soldes,
                ['401', '409'],
                'actif',
                [],
                $comptesUtilises,
                ['401' => 'debit']
            ),
            'lignes_tresorerie_passif' => $this->tresoreriePassifClasse5($soldes, $comptesUtilises),
            default => 0.0,
        };
    }

    protected function sommeClasses(Collection $soldes, array $classes, string $nature = 'actif'): float
    {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if (! in_array($s['classe'], $classes, true)) {
                continue;
            }
            if ($s['classe'] === 5) {
                continue;
            }
            $total += $nature === 'passif' ? $s['net_passif'] : $s['net_actif'];
        }

        return round($total, 2);
    }

    /**
     * Résultat net : Σ(crédit − débit) sur les comptes 6 et 7 (mouvements de la période).
     */
    public function resultatNetExercice(Collection $soldes): float
    {
        $total = 0.0;
        foreach ($soldes as $s) {
            if (! in_array($s['classe'], [6, 7], true)) {
                continue;
            }
            $total += (float) $s['mouvement_credit'] - (float) $s['mouvement_debit'];
        }

        return round($total, 2);
    }

    protected function brutAmortNet(Collection $soldes, array $prefixes, array $exclude = [], ?array &$comptesUtilises = null): array
    {
        $brut = 0.0;
        $amort = 0.0;
        foreach ($soldes as $num => $s) {
            if ($comptesUtilises !== null && isset($comptesUtilises[$num])) {
                continue;
            }
            if (! $this->matchPrefix($num, $prefixes, $exclude)) {
                continue;
            }
            if (preg_match('/^28|^29/', $num)) {
                $amort += max(0, $s['net_passif']);
            } else {
                $brut += max(0, $s['net_actif']);
            }
            if ($comptesUtilises !== null) {
                $comptesUtilises[$num] = true;
            }
        }
        $net = round($brut - $amort, 2);

        return [
            'brut' => round($brut, 2),
            'amort' => round($amort, 2),
            'net' => $net,
        ];
    }

    protected function montantLigne(array $def, Collection $soldes, ?array &$comptesUtilises = null): array
    {
        if (($def['type'] ?? '') === 'titre') {
            return ['brut' => null, 'amort' => null, 'net' => null, 'montant' => null];
        }

        $nature = $def['nature'] ?? 'actif';
        $prefixes = $def['prefixes'] ?? [];
        $exclude = $def['exclude_prefixes'] ?? [];

        if (! empty($def['tresorerie_signee']) && $prefixes) {
            $montant = $this->tresorerieActifClasse5($soldes, $comptesUtilises, $prefixes, $exclude, true);

            return ['brut' => $montant, 'amort' => 0.0, 'net' => $montant, 'montant' => $montant];
        }

        if (! empty($def['tresorerie_passif']) && $prefixes) {
            $montant = $this->tresoreriePassifClasse5($soldes, $comptesUtilises, $prefixes, $exclude);

            return ['brut' => $montant, 'amort' => 0.0, 'net' => $montant, 'montant' => $montant];
        }

        if (! empty($def['affichage_signe']) && $prefixes) {
            $montant = $this->sommePrefixesSignee($soldes, $prefixes, $exclude, $comptesUtilises, 'passif_odoo', $def);

            return ['brut' => $montant, 'amort' => 0.0, 'net' => $montant, 'montant' => $montant];
        }

        if ($nature === 'actif' && $prefixes && $this->ligneEstTresorerieActif($prefixes) && empty($def['tresorerie_signee'])) {
            $montant = $this->tresorerieActifClasse5($soldes, $comptesUtilises, $prefixes, $exclude);

            return ['brut' => $montant, 'amort' => 0.0, 'net' => $montant, 'montant' => $montant];
        }

        if ($nature === 'passif' && $prefixes && $this->ligneEstTresoreriePassif($prefixes) && empty($def['tresorerie_passif'])) {
            $montant = $this->tresoreriePassifClasse5(
                $soldes,
                $comptesUtilises,
                $this->prefixesIncluent($prefixes, ['56']) ? null : $prefixes,
                $exclude
            );

            return ['brut' => $montant, 'amort' => 0.0, 'net' => $montant, 'montant' => $montant];
        }

        if ($nature === 'actif' && $prefixes) {
            $ban = $this->brutAmortNet($soldes, $prefixes, $exclude, $comptesUtilises);

            return array_merge($ban, ['montant' => $ban['net']]);
        }

        $sensComptes = $def['sens_comptes'] ?? null;
        if (! empty($def['fournisseurs_debit']) && $prefixes) {
            $tmp = [];
            $montant = $this->sommePrefixesSignee($soldes, $prefixes, $exclude, $comptesUtilises ?? $tmp, 'actif', $def);
        } else {
            $montant = $prefixes
                ? $this->sommePrefixes($soldes, $prefixes, $nature, $exclude, $comptesUtilises, $sensComptes)
                : 0.0;
        }

        if (! empty($def['capital_positif'])) {
            $montant = max(0, $montant);
        }

        return ['brut' => $montant, 'amort' => 0.0, 'net' => $montant, 'montant' => $montant];
    }

    protected function sommePrefixesSignee(
        Collection $soldes,
        array $prefixes,
        array $exclude = [],
        ?array &$comptesUtilises = null,
        string $mode = 'actif',
        ?array $def = null
    ): float {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if ($comptesUtilises !== null && isset($comptesUtilises[$num])) {
                continue;
            }
            if (! $this->matchPrefix($num, $prefixes, $exclude)) {
                continue;
            }
            if (($s['classe'] ?? 0) === 5) {
                continue;
            }
            if (! empty($def['fournisseurs_credit']) && str_starts_with($num, '401')) {
                if ((float) $s['credit'] <= (float) $s['debit']) {
                    continue;
                }
            }
            if (! empty($def['fournisseurs_debit']) && str_starts_with($num, '401')) {
                if ((float) $s['debit'] <= (float) $s['credit']) {
                    continue;
                }
            }
            $montant = $this->soldeSigneCompte($s, $mode);
            if (abs($montant) < 0.01) {
                continue;
            }
            $total += $montant;
            if ($comptesUtilises !== null) {
                $comptesUtilises[$num] = true;
            }
        }

        return round($total, 2);
    }

    /**
     * @return array<int, array{num_compte: string, libelle: string, brut: float, amort: float, net: float}>
     */
    protected function comptesDetailPourLigne(array $def, Collection $soldes, ?array &$comptesUtilises = null): array
    {
        $prefixes = $def['prefixes'] ?? [];
        $exclude = $def['exclude_prefixes'] ?? [];
        if (empty($prefixes)) {
            return [];
        }

        $details = [];
        $mode = ! empty($def['affichage_signe']) ? 'passif_odoo' : 'actif';

        foreach ($soldes as $num => $s) {
            if ($comptesUtilises !== null && isset($comptesUtilises[$num])) {
                continue;
            }
            if (! $this->matchPrefix($num, $prefixes, $exclude)) {
                continue;
            }

            if (! empty($def['tresorerie_signee']) || ! empty($def['tresorerie_passif'])) {
                if (! $this->estCompteClasse5($num, $s)) {
                    continue;
                }
                if (! empty($def['tresorerie_signee'])) {
                    $montant = $this->soldeSigneCompte($s, 'actif');
                } else {
                    if ((float) $s['credit'] <= (float) $s['debit']) {
                        continue;
                    }
                    $montant = round((float) $s['credit'] - (float) $s['debit'], 2);
                }
            } elseif (! empty($def['sens_comptes'])) {
                $montant = $this->montantComptePourNature($num, $s, $def['nature'] ?? 'actif', $def['sens_comptes']);
            } elseif (! empty($def['affichage_signe'])) {
                if (! empty($def['fournisseurs_credit']) && str_starts_with($num, '401') && (float) $s['credit'] <= (float) $s['debit']) {
                    continue;
                }
                $montant = $this->soldeSigneCompte($s, 'passif_odoo');
            } else {
                $montant = max(0, ($def['nature'] ?? 'actif') === 'passif' ? $s['net_passif'] : $s['net_actif']);
            }

            if (abs($montant) < 0.01) {
                continue;
            }

            $details[] = [
                'num_compte' => $num,
                'libelle' => $s['libelle'] ?? $num,
                'brut' => $montant,
                'amort' => 0.0,
                'net' => $montant,
            ];
            if ($comptesUtilises !== null) {
                $comptesUtilises[$num] = true;
            }
        }

        usort($details, fn ($a, $b) => strcmp($a['num_compte'], $b['num_compte']));

        return $details;
    }

    protected function ligneEstTresorerieActif(array $prefixes): bool
    {
        return $this->prefixesIncluent($prefixes, ['50', '51', '52', '53', '54', '55', '57', '58']);
    }

    protected function ligneEstTresoreriePassif(array $prefixes): bool
    {
        return $this->prefixesIncluent($prefixes, ['56', '59']);
    }

    protected function prefixesIncluent(array $prefixes, array $cibles): bool
    {
        foreach ($prefixes as $p) {
            foreach ($cibles as $c) {
                if ($p === $c || str_starts_with((string) $c, (string) $p) || str_starts_with((string) $p, (string) $c)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function evaluerFormule(string $formule, array $valeurs): float
    {
        $expr = preg_replace_callback('/[A-Z][A-Z0-9]*/', function ($m) use ($valeurs) {
            return '('.(string) ($valeurs[$m[0]] ?? 0).')';
        }, strtoupper(str_replace(' ', '', $formule)));

        if (! preg_match('/^[0-9+\-\.\(\)\s]+$/', $expr)) {
            return 0.0;
        }

        try {
            return round((float) eval("return {$expr};"), 2);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    protected function construireLignes(
        array $definitions,
        Collection $soldesN,
        ?Collection $soldesN1 = null,
        ?float $resultatNetExercice = null
    ): array {
        $valeurs = [];
        $valeursBrut = [];
        $valeursN1 = [];
        $lignes = [];
        $comptesUtilises = [];

        foreach ($definitions as $def) {
            $type = $def['type'] ?? 'ligne';
            $ref = $def['ref'] ?? null;

            if ($type === 'titre') {
                $lignes[] = array_merge($def, [
                    'brut_n' => null, 'amort_n' => null, 'net_n' => null,
                    'net_n1' => null, 'montant_n' => null, 'montant_n1' => null,
                ]);
                continue;
            }

            if ($type === 'equilibre') {
                $montantN = $resultatNetExercice ?? 0.0;
                $lignes[] = array_merge($def, [
                    'brut_n' => $montantN,
                    'amort_n' => 0.0,
                    'net_n' => 0.0,
                    'balance' => 0.0,
                    'net_n1' => null,
                    'montant_n' => $montantN,
                    'montant_n1' => null,
                ]);
                if ($ref) {
                    $valeurs[$ref] = $montantN;
                }
                continue;
            }

            if ($type === 'formule' || ($type === 'total' && ! empty($def['formule']))) {
                $montantN = $this->evaluerFormuleSpeciale($def['formule'] ?? '', $valeurs, $soldesN, $soldesN1, true);
                $montantN1 = $soldesN1
                    ? $this->evaluerFormuleSpeciale($def['formule'] ?? '', $valeursN1, $soldesN1, null, false)
                    : 0.0;
                $brutN = $montantN;
                $amortN = 0.0;
                $netN = $montantN;
                $netN1 = $montantN1;
            } elseif ($type === 'total' && ! empty($def['somme_refs'])) {
                $montantN = 0.0;
                $montantN1 = 0.0;
                $brutN = 0.0;
                foreach ($def['somme_refs'] as $r) {
                    if (! empty($def['affichage_signe'])) {
                        $brutN += $valeursBrut[$r] ?? $valeurs[$r] ?? 0;
                    }
                    $montantN += $valeurs[$r] ?? 0;
                    $montantN1 += $valeursN1[$r] ?? 0;
                }
                if (empty($def['affichage_signe'])) {
                    $brutN = $montantN;
                }
                $amortN = 0.0;
                $netN = ! empty($def['affichage_signe']) ? $brutN : $montantN;
                $netN1 = $montantN1;
            } elseif ($type === 'total' && ! empty($def['somme'])) {
                $comptesSection = [];
                $montantN = $this->evaluerTotalSomme($def['somme'], $soldesN, $comptesSection);
                $comptesSectionN1 = [];
                $montantN1 = $soldesN1
                    ? $this->evaluerTotalSomme($def['somme'], $soldesN1, $comptesSectionN1)
                    : 0.0;
                $brutN = $montantN;
                $amortN = 0.0;
                $netN = $montantN;
                $netN1 = $montantN1;
            } else {
                $utilisesLigne = ! empty($def['detail_comptes']) ? null : $comptesUtilises;
                $mn = $this->montantLigne($def, $soldesN, $utilisesLigne);
                $comptesN1 = [];
                $mn1 = $soldesN1 ? $this->montantLigne($def, $soldesN1, $comptesN1) : ['net' => 0, 'brut' => 0, 'amort' => 0, 'montant' => 0];
                $montantN = $mn['montant'] ?? $mn['net'];
                $montantN1 = $mn1['montant'] ?? $mn1['net'];
                $brutN = $mn['brut'];
                $amortN = $mn['amort'];
                $netN = $mn['net'];
                $netN1 = $mn1['net'];

                if (! empty($def['resultat_exercice']) && $resultatNetExercice !== null) {
                    $tmpUtilises = [];
                    $solde13 = $this->sommePrefixesSignee($soldesN, ['13'], [], $tmpUtilises, 'passif');
                    $aIntegrer = round($resultatNetExercice - $solde13, 2);
                    if (abs($aIntegrer) >= 0.01) {
                        $montantN = $aIntegrer;
                        $brutN = null;
                        $netN = $aIntegrer;
                    }
                }
            }

            if ($ref) {
                $valeurs[$ref] = $montantN;
                $valeursBrut[$ref] = $brutN ?? $montantN;
                $valeursN1[$ref] = $montantN1;
                if (! empty($def['alias_ref'])) {
                    $valeurs[$def['alias_ref']] = $montantN;
                    $valeursBrut[$def['alias_ref']] = $brutN ?? $montantN;
                    $valeursN1[$def['alias_ref']] = $montantN1;
                }
            }

            $balance = $netN;
            if ($type === 'total' && ! empty($def['affichage_signe'])) {
                $balance = $brutN;
            }

            $lignes[] = array_merge($def, [
                'brut_n' => $brutN,
                'amort_n' => $amortN,
                'net_n' => $netN,
                'balance' => $balance,
                'net_n1' => $netN1,
                'montant_n' => $montantN,
                'montant_n1' => $montantN1,
            ]);

            if ($type === 'ligne' && ! empty($def['detail_comptes'])) {
                foreach ($this->comptesDetailPourLigne($def, $soldesN, $comptesUtilises) as $detail) {
                    $lignes[] = [
                        'type' => 'detail',
                        'ref' => null,
                        'libelle' => $detail['libelle'],
                        'num_compte' => $detail['num_compte'],
                        'note' => $def['note'] ?? null,
                        'brut_n' => $detail['brut'],
                        'amort_n' => $detail['amort'],
                        'net_n' => $detail['net'],
                        'balance' => $detail['net'],
                        'net_n1' => null,
                        'montant_n' => $detail['net'],
                        'montant_n1' => null,
                        'parent_ref' => $ref,
                    ];
                }
            }
        }

        return $lignes;
    }

    protected function appliquerBalanceTotalGeneralPassif(array $passif, float $totalActif): array
    {
        foreach ($passif as $i => $ligne) {
            if (empty($ligne['balance_egale_actif'])) {
                continue;
            }
            $passif[$i]['balance'] = round($totalActif, 2);
            $passif[$i]['net_n'] = round($totalActif, 2);
        }

        return $passif;
    }

    protected function extraireNetRefLignes(array $lignes, string $ref): float
    {
        foreach ($lignes as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['net_n'] ?? $l['balance'] ?? 0);
            }
        }

        return 0.0;
    }

    protected function evaluerFormuleSpeciale(
        string $formule,
        array $valeurs,
        Collection $soldes,
        ?Collection $soldesN1,
        bool $periodeN
    ): float {
        return match ($formule) {
            'tresorerie_ouverture' => $this->tresorerieNette($soldesN1 ?? $soldes, true),
            'tresorerie_cloture' => $this->tresorerieNette($soldes, false),
            'cafg' => $this->cafg($soldes),
            'variation_stocks' => $this->variationClasse($soldes, [3]),
            'variation_creances' => $this->variationClasse($soldes, [4], 'actif'),
            'variation_passif_circulant' => $this->sommePrefixes($soldes, ['40', '42', '43', '44', '45', '46', '47', '48', '49'], 'passif'),
            'variation_actif_hao' => $this->sommePrefixes($soldes, ['485', '488'], 'actif'),
            'decaissement_immo_incorp' => -abs($this->sommeMouvements($soldes, ['21'], 'debit')),
            'decaissement_immo_corp' => -abs($this->sommeMouvements($soldes, ['22', '23', '24', '25'], 'debit')),
            'decaissement_immo_fin' => -abs($this->sommeMouvements($soldes, ['26', '27'], 'debit')),
            'encaissement_cession_immo' => $this->sommeMouvements($soldes, ['82'], 'credit'),
            'encaissement_cession_fin' => $this->sommeMouvements($soldes, ['26', '27'], 'credit'),
            'prelevement_capital' => -abs($this->sommeMouvements($soldes, ['109'], 'debit')),
            'remboursement_emprunts' => -abs($this->sommeMouvements($soldes, ['16', '17'], 'debit')),
            default => $this->evaluerFormule($formule, $valeurs),
        };
    }

    protected function tresorerieNette(Collection $soldes, bool $ouverture = false): float
    {
        $actif = $this->tresorerieActifClasse5($soldes);
        $passif = $this->tresoreriePassifClasse5($soldes);

        return round($actif - $passif, 2);
    }

    protected function cafg(Collection $soldes): float
    {
        $resultat = $this->sommePrefixes($soldes, ['13'], 'passif') - $this->sommePrefixes($soldes, ['13'], 'actif');
        $dotations = $this->sommeMouvements($soldes, ['68', '69'], 'debit');
        $reprises = $this->sommeMouvements($soldes, ['78', '79'], 'credit');

        return round($resultat + $dotations - $reprises, 2);
    }

    protected function variationClasse(Collection $soldes, array $classes, string $nature = 'actif'): float
    {
        return $this->sommeClasses($soldes, $classes, $nature);
    }

    protected function sommeMouvements(Collection $soldes, array $prefixes, string $cote): float
    {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if (! $this->matchPrefix($num, $prefixes)) {
                continue;
            }
            $total += $cote === 'debit' ? $s['mouvement_debit'] : $s['mouvement_credit'];
        }

        return round($total, 2);
    }

    public function bilan(
        int $societeId,
        Exercice $exercice,
        string $dateArrete,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null,
        string $scopeDevise = 'consolide'
    ): array {
        $bilan = $this->bilanComptable->generer(
            $societeId,
            $exercice,
            $dateArrete,
            $deviseAffichage,
            $modeConversion,
            $scopeDevise
        );

        if ($exerciceN1) {
            $bilan['exercice_n1'] = $exerciceN1->libelle;
        }

        return $bilan;
    }

    protected function integrerResultatExerciceBilan(array $passif, Collection $soldes, float $resultatNet): array
    {
        $solde13Passif = $this->sommePrefixes($soldes, ['13'], 'passif');
        $solde13Actif = $this->sommePrefixes($soldes, ['13'], 'actif');
        $solde13 = round($solde13Passif - $solde13Actif, 2);
        $aIntegrer = round($resultatNet - $solde13, 2);

        if (abs($aIntegrer) < 0.01) {
            return $passif;
        }

        foreach ($passif as $i => $ligne) {
            $prefixes = $ligne['prefixes'] ?? [];
            if (! in_array('13', $prefixes, true) && ! $this->prefixesIncluent($prefixes, ['13'])) {
                continue;
            }
            $net = (float) ($ligne['net_n'] ?? 0);
            $passif[$i]['net_n'] = round($net + $aIntegrer, 2);
            $passif[$i]['brut_n'] = $passif[$i]['net_n'];
            $passif[$i]['montant_n'] = $passif[$i]['net_n'];
            break;
        }

        if (abs($aIntegrer) >= 0.01) {
            foreach ($passif as $j => $ligne) {
                if (($ligne['ref'] ?? '') === 'CP') {
                    $passif[$j]['net_n'] = round((float) ($ligne['net_n'] ?? 0) + $aIntegrer, 2);
                    $passif[$j]['brut_n'] = $passif[$j]['net_n'];
                    $passif[$j]['montant_n'] = $passif[$j]['net_n'];
                    break;
                }
            }
        }

        return $passif;
    }

    protected function recalculerTotalsRefs(array $lignes): array
    {
        $valeurs = [];
        $valeursN1 = [];

        foreach ($lignes as $l) {
            $ref = $l['ref'] ?? null;
            if ($ref) {
                $valeurs[$ref] = (float) ($l['net_n'] ?? 0);
                $valeursN1[$ref] = (float) ($l['net_n1'] ?? 0);
            }
        }

        foreach ($lignes as $i => $l) {
            if (($l['type'] ?? '') !== 'total' || empty($l['somme_refs'])) {
                continue;
            }
            $montantN = 0.0;
            $montantN1 = 0.0;
            foreach ($l['somme_refs'] as $r) {
                $montantN += $valeurs[$r] ?? 0;
                $montantN1 += $valeursN1[$r] ?? 0;
            }
            $lignes[$i]['net_n'] = round($montantN, 2);
            $lignes[$i]['brut_n'] = $lignes[$i]['net_n'];
            $lignes[$i]['montant_n'] = $lignes[$i]['net_n'];
            $lignes[$i]['net_n1'] = round($montantN1, 2);
            $lignes[$i]['montant_n1'] = $lignes[$i]['net_n1'];
            if (! empty($l['ref'])) {
                $valeurs[$l['ref']] = $lignes[$i]['net_n'];
                $valeursN1[$l['ref']] = $lignes[$i]['net_n1'];
            }
        }

        return $lignes;
    }

    protected function extraireNetRef(array $lignes, string $ref): float
    {
        foreach ($lignes as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['net_n'] ?? 0);
            }
        }

        return 0.0;
    }

    public function compteResultat(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null,
        string $scopeDevise = 'consolide'
    ): array {
        $definitions = config('syscohada_etats.compte_resultat');
        $soldesN = $this->soldesComptes($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion, $scopeDevise);
        $soldesN1 = $exerciceN1
            ? $this->soldesComptes($societeId, $exerciceN1, $exerciceN1->date_fin->format('Y-m-d'), $deviseAffichage, $modeConversion, $scopeDevise)
            : null;

        $lignes = $this->construireCompteResultat($definitions, $soldesN, $soldesN1);

        return [
            'titre' => 'COMPTE DE RÉSULTAT — Système normal',
            'periode' => $exercice->date_debut->format('d/m/Y').' au '.date('d/m/Y', strtotime($dateFin)),
            'exercice_n' => $exercice->libelle,
            'exercice_n1' => $exerciceN1?->libelle,
            'lignes' => $lignes,
            'devise' => $deviseAffichage,
            'scope_devise' => $scopeDevise,
        ];
    }

    protected function construireCompteResultat(array $definitions, Collection $soldesN, ?Collection $soldesN1): array
    {
        $valeurs = [];
        $valeursN1 = [];
        $lignes = [];

        foreach ($definitions as $def) {
            $ref = $def['ref'] ?? null;
            $type = $def['type'] ?? 'ligne';

            if ($type === 'formule') {
                $montantN = $this->evaluerFormule($def['formule'], $valeurs);
                $montantN1 = $this->evaluerFormule($def['formule'], $valeursN1);
            } else {
                $prefixes = $def['prefixes'] ?? [];
                $montantN = $this->sommeMouvements($soldesN, $prefixes, 'credit')
                    - $this->sommeMouvements($soldesN, $prefixes, 'debit');
                $montantN1 = $soldesN1
                    ? $this->sommeMouvements($soldesN1, $prefixes, 'credit')
                        - $this->sommeMouvements($soldesN1, $prefixes, 'debit')
                    : 0.0;
                $nature = $def['nature'] ?? 'produit';
                if ($nature === 'charge') {
                    $montantN = -$montantN;
                    $montantN1 = -$montantN1;
                }
            }

            if ($ref) {
                $valeurs[$ref] = $montantN;
                $valeursN1[$ref] = $montantN1;
            }

            $lignes[] = array_merge($def, [
                'montant_n' => round($montantN, 2),
                'montant_n1' => round($montantN1, 2),
            ]);
        }

        return $lignes;
    }

    public function fluxTresorerie(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null
    ): array {
        return $this->fluxTresorerie->generer(
            $societeId,
            $exercice,
            $dateFin,
            $deviseAffichage,
            $modeConversion,
            $exerciceN1
        );
    }

    public function variationCapitauxPropres(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null
    ): array {
        $definitions = config('syscohada_etats.variation_kp');
        $soldesN = $this->soldesComptes($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion);
        $soldesN1 = $exerciceN1
            ? $this->soldesComptes($societeId, $exerciceN1, $exerciceN1->date_fin->format('Y-m-d'), $deviseAffichage, $modeConversion)
            : null;

        $lignes = [];
        foreach ($definitions as $def) {
            $ouv = $soldesN1 ? $this->sommePrefixes($soldesN1, $def['prefixes'], 'passif') : 0.0;
            $clo = $this->sommePrefixes($soldesN, $def['prefixes'], 'passif');
            $lignes[] = array_merge($def, [
                'ouverture' => round($ouv, 2),
                'variation' => round($clo - $ouv, 2),
                'cloture' => round($clo, 2),
            ]);
        }

        return [
            'titre' => 'TABLEAU DE VARIATION DES CAPITAUX PROPRES',
            'exercice_n' => $exercice->libelle,
            'exercice_n1' => $exerciceN1?->libelle,
            'lignes' => $lignes,
            'devise' => $deviseAffichage,
        ];
    }

    public function annexes(): array
    {
        $notes = config('syscohada_etats.notes', []);
        $sections = config('syscohada_etats.annexes', []);

        return [
            'titre' => 'ANNEXES SYSCOHADA',
            'notes' => $notes,
            'sections' => $sections,
        ];
    }

    public function comparatif(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine'
    ): array {
        $n1 = $this->exercicePrecedent($societeId, $exercice);
        $bilan = $this->bilan($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion, $n1);
        $cr = $this->compteResultat($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion, $n1);

        return [
            'bilan' => $bilan,
            'compte_resultat' => $cr,
            'resume' => [
                'total_actif_n' => $this->extraireTotal($bilan, 'TA'),
                'resultat_net_n' => $this->extraireRef($cr['lignes'] ?? [], 'XI'),
            ],
        ];
    }

    protected function extraireRef(array $lignes, string $ref): float
    {
        foreach ($lignes as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['montant_n'] ?? 0);
            }
        }

        return 0.0;
    }

    protected function extraireTotal(array $bilan, string $ref): float
    {
        foreach (array_merge($bilan['actif'] ?? [], $bilan['passif'] ?? []) as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['net_n'] ?? 0);
            }
        }

        return 0.0;
    }

}
