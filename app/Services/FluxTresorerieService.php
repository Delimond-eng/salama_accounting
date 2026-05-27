<?php

namespace App\Services;

use App\Models\Exercice;
use Illuminate\Support\Collection;

/**
 * TFT SYSCOHADA — méthode indirecte, exclusivement depuis le journal (lignes_ecritures).
 * Aucune reconstruction à partir du bilan agrégé.
 */
class FluxTresorerieService
{
    private const TOLERANCE = 0.02;

    public function __construct(
        protected LivresComptablesService $livres,
        protected BilanComptableService $bilanComptable
    ) {}

    public function generer(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null,
        string $scopeDevise = 'consolide'
    ): array {
        $ctxN = $this->chargerContexte($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion, $scopeDevise);
        $ctxN1 = $exerciceN1
            ? $this->chargerContexte(
                $societeId,
                $exerciceN1,
                $exerciceN1->date_fin->format('Y-m-d'),
                $deviseAffichage,
                $modeConversion,
                $scopeDevise
            )
            : null;

        $valeursN = $this->calculerValeurs($ctxN);
        $valeursN1 = $ctxN1 ? $this->calculerValeurs($ctxN1) : [];

        $lignes = $this->construireLignes(
            config('syscohada_etats.flux_tresorerie', []),
            $valeursN,
            $valeursN1
        );

        $controle = $this->controleTresorerie($valeursN);

        return [
            'titre' => 'ÉTAT DES FLUX DE TRÉSORERIE',
            'exercice_n' => $exercice->libelle,
            'exercice_n1' => $exerciceN1?->libelle,
            'lignes' => $lignes,
            'devise' => $deviseAffichage,
            'controle' => $controle,
            'pipeline' => [
                'source' => 'lignes_ecritures',
                'methode' => 'indirecte_syscohada',
                'bilan' => false,
            ],
        ];
    }

    /**
     * @return array{
     *   soldes_fin: Collection,
     *   soldes_ouverture: Collection,
     *   societe_id: int,
     *   exercice: Exercice,
     *   date_fin: string
     * }
     */
    protected function chargerContexte(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        string $scopeDevise = 'consolide'
    ): array {
        $dateDebut = $exercice->date_debut->format('Y-m-d');
        $balance = $this->livres->balanceGenerale(
            $societeId,
            $exercice->id,
            $dateDebut,
            $dateFin,
            $deviseAffichage,
            $modeConversion,
            null,
            $scopeDevise
        );

        $soldesFin = $this->soldesDepuisBalance(collect($balance['lignes']), false);
        $soldesOuverture = $this->soldesDepuisBalance(collect($balance['lignes']), true);

        return [
            'societe_id' => $societeId,
            'exercice' => $exercice,
            'date_fin' => $dateFin,
            'soldes_fin' => $soldesFin,
            'soldes_ouverture' => $soldesOuverture,
        ];
    }

    /**
     * Soldes par compte issus de la balance (agrégation journal).
     *
     * @param  Collection<int, array>  $lignes
     */
    protected function soldesDepuisBalance(Collection $lignes, bool $soldeDebut): Collection
    {
        return $lignes->mapWithKeys(function (array $row) use ($soldeDebut) {
            $num = (string) $row['num_compte'];
            if ($soldeDebut) {
                $debit = round((float) ($row['solde_debut_debiteur'] ?? 0), 2);
                $credit = round((float) ($row['solde_debut_crediteur'] ?? 0), 2);
            } else {
                $debit = round((float) ($row['solde_fin_debiteur'] ?? 0), 2);
                $credit = round((float) ($row['solde_fin_crediteur'] ?? 0), 2);
            }

            $classe = (int) substr($num, 0, 1);

            return [$num => [
                'num_compte' => $num,
                'libelle' => $row['libelle'] ?? $num,
                'classe' => $classe,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($debit - $credit, 2),
                'net_actif' => round($debit - $credit, 2),
                'net_passif' => round($credit - $debit, 2),
                'mouvement_debit' => round((float) ($row['mouvement_debit'] ?? 0), 2),
                'mouvement_credit' => round((float) ($row['mouvement_credit'] ?? 0), 2),
            ]];
        });
    }

    /**
     * @param  array{soldes_fin: Collection, soldes_ouverture: Collection, societe_id: int, exercice: Exercice, date_fin: string}  $ctx
     * @return array<string, float>
     */
    protected function calculerValeurs(array $ctx): array
    {
        $fin = $ctx['soldes_fin'];
        $ouv = $ctx['soldes_ouverture'];

        $za = $this->tresorerieNette($ouv);
        $zh = $this->tresorerieNette($fin);

        $fa = $this->cafg($ctx);
        $fb = $this->variationFluxActif($fin, $ouv, ['485', '488']);
        $fc = $this->variationFluxActif($fin, $ouv, ['3']);
        $fd = $this->variationFluxActif($fin, $ouv, ['411']);
        $fe = $this->variationFluxPassif($fin, $ouv, ['401']);

        $bfg = round($fb + $fc + $fd + $fe, 2);
        $zb = round($fa + $bfg, 2);

        $ff = -abs($this->sommeMouvements($fin, ['21'], 'debit'));
        $fg = -abs($this->sommeMouvements($fin, ['22', '23', '24', '25'], 'debit'));
        $fh = -abs($this->sommeMouvements($fin, ['26', '27'], 'debit'));
        $fi = $this->sommeMouvements($fin, ['82'], 'credit');
        $fj = $this->sommeMouvements($fin, ['26', '27'], 'credit');
        $zc = round($ff + $fg + $fh + $fi + $fj, 2);

        $fk = $this->sommeMouvements($fin, ['101', '104'], 'credit')
            - $this->sommeMouvements($fin, ['101', '104'], 'debit');
        $fl = $this->sommeMouvements($fin, ['14'], 'credit')
            - $this->sommeMouvements($fin, ['14'], 'debit');
        $fm = -abs($this->sommeMouvements($fin, ['109'], 'debit'));
        $fn = $this->sommeMouvements($fin, ['457'], 'credit')
            - $this->sommeMouvements($fin, ['457'], 'debit');
        $zd = round($fk + $fl + $fm + $fn, 2);

        $fo = $this->sommeMouvements($fin, ['16'], 'credit')
            - $this->sommeMouvements($fin, ['16'], 'debit');
        $fp = $this->sommeMouvements($fin, ['17'], 'credit')
            - $this->sommeMouvements($fin, ['17'], 'debit');
        $fq = -abs($this->sommeMouvements($fin, ['16', '17'], 'debit'));
        $ze = round($fo + $fp + $fq, 2);

        $zf = round($zd + $ze, 2);
        $zg = round($zb + $zc + $zf, 2);

        $deltaTreso = round($zh - $za, 2);
        $ctrl = round($zg - $deltaTreso, 2);

        return [
            'ZA' => $za,
            'FA' => $fa,
            'FB' => $fb,
            'FC' => $fc,
            'FD' => $fd,
            'FE' => $fe,
            'BFG' => $bfg,
            'ZB' => $zb,
            'FF' => $ff,
            'FG' => $fg,
            'FH' => $fh,
            'FI' => $fi,
            'FJ' => $fj,
            'ZC' => $zc,
            'FK' => $fk,
            'FL' => $fl,
            'FM' => $fm,
            'FN' => $fn,
            'ZD' => $zd,
            'FO' => $fo,
            'FP' => $fp,
            'FQ' => $fq,
            'ZE' => $ze,
            'ZF' => $zf,
            'ZG' => $zg,
            'ZH' => $zh,
            'CTRL' => $ctrl,
            '_delta_tresorerie' => $deltaTreso,
            '_tresorerie_ouverture' => $za,
            '_tresorerie_cloture' => $zh,
        ];
    }

    /**
     * CAFG = résultat net (6–7, journal) + dotations − reprises.
     */
    protected function cafg(array $ctx): float
    {
        $resultat = $this->bilanComptable->resultatNetExercice(
            $ctx['societe_id'],
            $ctx['exercice'],
            $ctx['date_fin']
        );

        $dotations = $this->sommeMouvements($ctx['soldes_fin'], ['68', '69'], 'debit');
        $reprises = $this->sommeMouvements($ctx['soldes_fin'], ['78', '79'], 'credit');

        return round($resultat + $dotations - $reprises, 2);
    }

    /**
     * Trésorerie 521 + 571 : solde naturel actif (débit − crédit).
     */
    protected function tresorerieNette(Collection $soldes): float
    {
        return round(
            $this->sommeSoldesActif($soldes, ['521', '571']),
            2
        );
    }

    /**
     * Variation BFR actif : ouverture − clôture (hausse actif = emploi de trésorerie).
     */
    protected function variationFluxActif(Collection $fin, Collection $ouv, array $prefixes): float
    {
        $soldeFin = $this->sommeSoldesActif($fin, $prefixes);
        $soldeOuv = $this->sommeSoldesActif($ouv, $prefixes);

        return round($soldeOuv - $soldeFin, 2);
    }

    /**
     * Variation BFR passif : clôture − ouverture (401 débiteur inclus via solde signé passif).
     */
    protected function variationFluxPassif(Collection $fin, Collection $ouv, array $prefixes): float
    {
        $soldeFin = $this->sommeSoldesPassif($fin, $prefixes);
        $soldeOuv = $this->sommeSoldesPassif($ouv, $prefixes);

        return round($soldeFin - $soldeOuv, 2);
    }

    protected function sommeSoldesActif(Collection $soldes, array $prefixes): float
    {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if (! $this->matchPrefix((string) $num, $prefixes)) {
                continue;
            }
            $total += (float) $s['net_actif'];
        }

        return round($total, 2);
    }

    protected function sommeSoldesPassif(Collection $soldes, array $prefixes): float
    {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if (! $this->matchPrefix((string) $num, $prefixes)) {
                continue;
            }
            $total += (float) $s['net_passif'];
        }

        return round($total, 2);
    }

    protected function sommeMouvements(Collection $soldes, array $prefixes, string $cote): float
    {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            if (! $this->matchPrefix((string) $num, $prefixes)) {
                continue;
            }
            $total += $cote === 'debit'
                ? (float) $s['mouvement_debit']
                : (float) $s['mouvement_credit'];
        }

        return round($total, 2);
    }

    protected function matchPrefix(string $numCompte, array $prefixes): bool
    {
        foreach ($prefixes as $p) {
            if (str_starts_with($numCompte, (string) $p)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluerFormule(string $formule, array $valeurs): float
    {
        $expr = preg_replace_callback(
            '/[A-Z][A-Z0-9]*/',
            fn ($m) => (string) ($valeurs[$m[0]] ?? 0),
            $formule
        );

        try {
            return round((float) eval('return '.$expr.';'), 2);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     * @return list<array<string, mixed>>
     */
    protected function construireLignes(array $definitions, array $valeursN, array $valeursN1): array
    {
        $calculeesN = $valeursN;
        $calculeesN1 = $valeursN1;
        $lignes = [];

        foreach ($definitions as $def) {
            $ref = $def['ref'] ?? null;
            $type = $def['type'] ?? 'ligne';

            if ($type === 'titre') {
                $lignes[] = array_merge($def, ['montant_n' => null, 'montant_n1' => null]);
                continue;
            }

            if ($ref && array_key_exists($ref, $valeursN)) {
                $montantN = (float) $valeursN[$ref];
                $montantN1 = array_key_exists($ref, $valeursN1) ? (float) $valeursN1[$ref] : 0.0;
            } elseif ($type === 'formule' && ! empty($def['formule'])) {
                $montantN = $this->evaluerFormule((string) $def['formule'], $calculeesN);
                $montantN1 = $valeursN1 !== []
                    ? $this->evaluerFormule((string) $def['formule'], $calculeesN1)
                    : 0.0;
            } elseif (! empty($def['prefixes'])) {
                $montantN = 0.0;
                $montantN1 = 0.0;
            } else {
                $montantN = 0.0;
                $montantN1 = 0.0;
            }

            if ($ref) {
                $calculeesN[$ref] = $montantN;
                $calculeesN1[$ref] = $montantN1;
            }

            $lignes[] = array_merge($def, [
                'montant_n' => round($montantN, 2),
                'montant_n1' => round($montantN1, 2),
            ]);
        }

        return $lignes;
    }

    /**
     * @param  array<string, float>  $valeurs
     * @return array<string, mixed>
     */
    protected function controleTresorerie(array $valeurs): array
    {
        $deltaTreso = $valeurs['_delta_tresorerie'] ?? round(($valeurs['ZH'] ?? 0) - ($valeurs['ZA'] ?? 0), 2);
        $zg = $valeurs['ZG'] ?? 0;
        $ecart = round($zg - $deltaTreso, 2);

        return [
            'variation_tft_zg' => $zg,
            'variation_tresorerie_521_571' => $deltaTreso,
            'tresorerie_ouverture' => $valeurs['_tresorerie_ouverture'] ?? $valeurs['ZA'] ?? 0,
            'tresorerie_cloture' => $valeurs['_tresorerie_cloture'] ?? $valeurs['ZH'] ?? 0,
            'ecart_controle' => $ecart,
            'ok' => abs($ecart) < self::TOLERANCE,
            'formule' => 'ZG − (ZH − ZA) = 0 ; ZH − ZA = Δ Banque(521) + Caisse(571)',
        ];
    }
}
