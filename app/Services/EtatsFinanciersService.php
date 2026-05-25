<?php

namespace App\Services;

use App\Models\Exercice;
use App\Models\Societe;
use Illuminate\Support\Collection;
use App\Exceptions\BilanDesequilibreException;

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

    public function bilan(
        int $societeId,
        Exercice $exercice,
        string $dateArrete,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null
    ): array {
        $bilan = $this->bilanComptable->generer(
            $societeId,
            $exercice,
            $dateArrete,
            $deviseAffichage,
            $modeConversion
        );

        if ($exerciceN1) {
            $bilan['exercice_n1'] = $exerciceN1->libelle;
        }

        return $bilan;
    }

    public function compteResultat(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null
    ): array {
        $definitions = config('syscohada_etats.compte_resultat');
        $soldesN = $this->soldesComptes($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion);
        $soldesN1 = $exerciceN1
            ? $this->soldesComptes($societeId, $exerciceN1, $exerciceN1->date_fin->format('Y-m-d'), $deviseAffichage, $modeConversion)
            : null;

        $lignes = $this->construireCompteResultat($definitions, $soldesN, $soldesN1);

        return [
            'titre' => 'COMPTE DE RÉSULTAT — Système normal',
            'periode' => $exercice->date_debut->format('d/m/Y').' au '.date('d/m/Y', strtotime($dateFin)),
            'exercice_n' => $exercice->libelle,
            'exercice_n1' => $exerciceN1?->libelle,
            'lignes' => $lignes,
            'devise' => $deviseAffichage,
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

    protected function sommeMouvements(Collection $soldes, array $prefixes, string $cote): float
    {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            $match = false;
            foreach ($prefixes as $p) {
                if (str_starts_with((string)$num, (string)$p)) {
                    $match = true;
                    break;
                }
            }
            if ($match) {
                $total += $cote === 'debit' ? $s['mouvement_debit'] : $s['mouvement_credit'];
            }
        }
        return round($total, 2);
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

    protected function sommePrefixes(Collection $soldes, array $prefixes, string $nature = 'actif', array $exclude = [], ?array $sensComptes = null): float {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            $match = false;
            foreach ($prefixes as $p) {
                if (str_starts_with((string)$num, (string)$p)) {
                    $match = true;
                    break;
                }
            }
            if ($match) {
                foreach ($exclude as $ex) {
                    if (str_starts_with((string)$num, (string)$ex)) {
                        $match = false;
                        break;
                    }
                }
            }
            if ($match) {
                if ($sensComptes) {
                    foreach ($sensComptes as $prefix => $sens) {
                        if (str_starts_with((string)$num, (string)$prefix)) {
                            $val = $sens === 'debit' ? $s['net_actif'] : $s['net_passif'];
                            $total += max(0, $val);
                            continue 2;
                        }
                    }
                }
                $val = $nature === 'passif' ? $s['net_passif'] : $s['net_actif'];
                $total += $val;
            }
        }
        return round($total, 2);
    }

    protected function sommePrefixesAmort(Collection $soldes, array $prefixes, array $exclude = []): float {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            $match = false;
            foreach ($prefixes as $p) {
                if (str_starts_with((string)$num, (string)$p)) {
                    $match = true;
                    break;
                }
            }
            if ($match) {
                foreach ($exclude as $ex) {
                    if (str_starts_with((string)$num, (string)$ex)) {
                        $match = false;
                        break;
                    }
                }
            }
            if ($match) {
                if (preg_match('/^28|^29/', (string)$num)) {
                    $total -= $s['net_passif']; // Amortissement soustrait du brut
                } else {
                    $total += $s['net_actif'];
                }
            }
        }
        return round($total, 2);
    }

    public function annexes(): array
    {
        return [
            'titre' => 'ANNEXES SYSCOHADA',
            'notes' => config('syscohada_etats.notes', []),
            'sections' => config('syscohada_etats.annexes', []),
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
        $definitions = config('syscohada_etats.bilan');

        $soldesN = $this->soldesComptes($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion);
        $soldesN1 = $n1
            ? $this->soldesComptes($societeId, $n1, $n1->date_fin->format('Y-m-d'), $deviseAffichage, $modeConversion)
            : collect();

        $resN = $this->bilanComptable->resultatNetExercice($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion);
        $resN1 = $n1
            ? $this->bilanComptable->resultatNetExercice($societeId, $n1, $n1->date_fin->format('Y-m-d'), $deviseAffichage, $modeConversion)
            : 0.0;

        $bilanActif = $this->construireBilanComparatif($definitions['actif'], $soldesN, $soldesN1, $resN, $resN1, 'actif');
        $bilanPassif = $this->construireBilanComparatif($definitions['passif'], $soldesN, $soldesN1, $resN, $resN1, 'passif');

        $cr = $this->compteResultat($societeId, $exercice, $dateFin, $deviseAffichage, $modeConversion, $n1);

        return [
            'bilan' => [
                'actif' => $bilanActif,
                'passif' => $bilanPassif,
            ],
            'compte_resultat' => $cr,
            'resume' => [
                'total_actif_n' => $this->extraireRefLignes($bilanActif, 'BZ'),
                'resultat_net_n' => $resN,
            ],
        ];
    }

    protected function construireBilanComparatif(array $defs, Collection $soldesN, Collection $soldesN1, float $resN, float $resN1, string $nature): array {
        $lignes = [];
        $valeursN = [];
        $valeursN1 = [];

        foreach ($defs as $def) {
            $type = $def['type'] ?? 'ligne';
            $netN = 0.0;
            $netN1 = 0.0;

            if ($type === 'titre') {
                $lignes[] = array_merge($def, ['net_n' => null, 'net_n1' => null]);
                continue;
            }

            if ($type === 'ligne') {
                $prefixes = $def['prefixes'] ?? [];
                $exclude = $def['exclude_prefixes'] ?? [];
                $sens = $def['sens_comptes'] ?? null;

                if ($nature === 'actif' && !empty($prefixes) && $this->prefixesIncluent($prefixes, ['2'])) {
                    $netN = $this->sommePrefixesAmort($soldesN, $prefixes, $exclude);
                    $netN1 = $this->sommePrefixesAmort($soldesN1, $prefixes, $exclude);
                } else {
                    $netN = $this->sommePrefixes($soldesN, $prefixes, $def['nature'] ?? $nature, $exclude, $sens);
                    $netN1 = $this->sommePrefixes($soldesN1, $prefixes, $def['nature'] ?? $nature, $exclude, $sens);
                }

                if (!empty($def['resultat_exercice'])) {
                    $netN = $resN;
                    $netN1 = $resN1;
                }
            } elseif ($type === 'total') {
                if (!empty($def['somme_refs'])) {
                    foreach ($def['somme_refs'] as $ref) {
                        $netN += ($valeursN[$ref] ?? 0.0);
                        $netN1 += ($valeursN1[$ref] ?? 0.0);
                    }
                }
            } elseif ($type === 'equilibre') {
                $netN = $resN;
                $netN1 = $resN1;
            }

            if (isset($def['ref'])) {
                $valeursN[$def['ref']] = $netN;
                $valeursN1[$def['ref']] = $netN1;
                if (!empty($def['alias_ref'])) {
                    $valeursN[$def['alias_ref']] = $netN;
                    $valeursN1[$def['alias_ref']] = $netN1;
                }
            }

            $lignes[] = array_merge($def, [
                'net_n' => round($netN, 2),
                'net_n1' => round($netN1, 2),
            ]);
        }

        return $lignes;
    }

    protected function prefixesIncluent(array $prefixes, array $cibles): bool {
        foreach ($prefixes as $p) {
            foreach ($cibles as $c) {
                if (str_starts_with((string)$p, (string)$c)) return true;
            }
        }
        return false;
    }

    protected function extraireRefLignes(array $lignes, string $ref): float
    {
        foreach ($lignes as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['net_n'] ?? 0);
            }
        }
        return 0.0;
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

    protected function extraireTotal(?array $bilan, string $ref): float
    {
        if (!$bilan) return 0.0;
        foreach (array_merge($bilan['actif'] ?? [], $bilan['passif'] ?? []) as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['net_n'] ?? 0);
            }
        }
        return 0.0;
    }
}
