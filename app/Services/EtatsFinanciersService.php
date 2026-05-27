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

    public function bilan(
        int $societeId,
        Exercice $exercice,
        string $dateArrete,
        string $deviseAffichage = 'CDF',
        string $modeConversion = 'origine',
        ?Exercice $exerciceN1 = null,
        string $scopeDevise = 'consolide'
    ): array {
        // Génération N
        $bilan = $this->bilanComptable->generer(
            $societeId,
            $exercice,
            $dateArrete,
            $deviseAffichage,
            $modeConversion,
            $scopeDevise
        );

        // Intégration N-1 pour le comparatif
        if ($exerciceN1) {
            $bilan['exercice_n1'] = $exerciceN1->libelle;
            $bilanN1 = $this->bilanComptable->generer(
                $societeId,
                $exerciceN1,
                $exerciceN1->date_fin->format('Y-m-d'),
                $deviseAffichage,
                $modeConversion
            );

            // Mapper les montants N-1 dans la structure N
            $bilan['actif'] = $this->injecterN1DansBilan($bilan['actif'], $bilanN1['actif']);
            $bilan['passif'] = $this->injecterN1DansBilan($bilan['passif'], $bilanN1['passif']);
            $bilan['total_actif_n1'] = $bilanN1['total_actif'];
            $bilan['total_passif_n1'] = $bilanN1['total_passif'];
            $bilan['resultat_exercice_n1'] = $bilanN1['resultat_exercice'];
        }

        return $bilan;
    }

    protected function injecterN1DansBilan(array $lignesN, array $lignesN1): array
    {
        $mapN1 = [];
        foreach ($lignesN1 as $l) {
            $key = $l['num_compte'] ?? $l['libelle'] ?? 'unknown';
            $mapN1[$key] = $l['net_n'] ?? 0;
        }

        foreach ($lignesN as $i => $l) {
            $key = $l['num_compte'] ?? $l['libelle'] ?? 'unknown';
            $lignesN[$i]['net_n1'] = $mapN1[$key] ?? 0;
        }

        return $lignesN;
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
                if (str_starts_with($num, (string)$p)) {
                    $match = true;
                    break;
                }
            }
            if (!$match) continue;
            $total += $cote === 'debit' ? (float)($s['mouvement_debit'] ?? 0) : (float)($s['mouvement_credit'] ?? 0);
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

    protected function sommePrefixes(Collection $soldes, array $prefixes, string $nature): float
    {
        $total = 0.0;
        foreach ($soldes as $num => $s) {
            $match = false;
            foreach ($prefixes as $p) {
                if (str_starts_with($num, (string)$p)) {
                    $match = true;
                    break;
                }
            }
            if (!$match) continue;
            $total += $nature === 'passif' ? $s['net_passif'] : $s['net_actif'];
        }
        return round($total, 2);
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
                'total_actif_n' => $bilan['total_actif'] ?? 0,
                'total_actif_n1' => $bilan['total_actif_n1'] ?? 0,
                'resultat_net_n' => $this->extraireRef($cr['lignes'] ?? [], 'XI'),
                'resultat_net_n1' => $this->extraireRef($cr['lignes'] ?? [], 'XI', true),
            ],
        ];
    }

    protected function extraireRef(array $lignes, string $ref, bool $n1 = false): float
    {
        foreach ($lignes as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($n1 ? ($l['montant_n1'] ?? 0) : ($l['montant_n'] ?? 0));
            }
        }

        return 0.0;
    }
}
