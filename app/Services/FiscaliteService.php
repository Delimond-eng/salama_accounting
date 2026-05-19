<?php

namespace App\Services;

use App\Models\DeclarationFiscale;
use App\Models\Exercice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FiscaliteService
{
    public function __construct(
        protected LivresComptablesService $livres,
        protected EtatsFinanciersService $etats
    ) {}

    public function exerciceCourant(int $societeId): ?Exercice
    {
        return $this->livres->exerciceCourant($societeId);
    }

    protected function mouvementsPeriode(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        string $devise = 'CDF',
        string $mode = 'origine'
    ): Collection {
        $societe = \App\Models\Societe::findOrFail($societeId);
        $this->livres->optionsDefaut($societe);

        $rows = DB::table('lignes_ecritures as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->where('l.societe_id', $societeId)
            ->where('l.exercice_id', $exerciceId)
            ->where('e.statut', 'validee')
            ->whereNull('e.deleted_at')
            ->whereBetween('e.date_ecriture', [$dateDebut, $dateFin])
            ->select(['l.num_compte', 'l.debit', 'l.credit', 'e.devise', 'e.taux_change', 'e.date_ecriture'])
            ->get();

        $conv = app(DeviseConversionService::class);
        $conv->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        return $rows->map(function ($r) use ($conv, $devise, $societeId, $mode) {
            $debit = $conv->convertir(
                (float) $r->debit,
                strtoupper($r->devise ?? 'CDF'),
                $devise,
                (float) ($r->taux_change ?? 1),
                $societeId,
                $r->date_ecriture,
                $mode
            );
            $credit = $conv->convertir(
                (float) $r->credit,
                strtoupper($r->devise ?? 'CDF'),
                $devise,
                (float) ($r->taux_change ?? 1),
                $societeId,
                $r->date_ecriture,
                $mode
            );

            return [
                'num_compte' => $r->num_compte,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];
        });
    }

    protected function sommePrefixesMouvements(Collection $lignes, array $prefixes, string $sens): float
    {
        $total = 0.0;
        foreach ($lignes as $l) {
            $match = false;
            foreach ($prefixes as $p) {
                if (str_starts_with($l['num_compte'], $p)) {
                    $match = true;
                    break;
                }
            }
            if (! $match) {
                continue;
            }
            $total += $sens === 'credit' ? $l['credit'] - $l['debit'] : $l['debit'] - $l['credit'];
        }

        return round(max(0, $total), 2);
    }

    public function tvaCollectee(int $societeId, Exercice $exercice, string $dateDebut, string $dateFin, string $devise = 'CDF', string $mode = 'origine'): array
    {
        $prefixes = config('fiscalite.prefixes_tva_collectee', ['443']);
        $lignes = $this->mouvementsPeriode($societeId, $exercice->id, $dateDebut, $dateFin, $devise, $mode);
        $total = $this->sommePrefixesMouvements($lignes, $prefixes, 'credit');

        $detail = [];
        foreach ($lignes as $l) {
            foreach ($prefixes as $p) {
                if (str_starts_with($l['num_compte'], $p)) {
                    $m = round($l['credit'] - $l['debit'], 2);
                    if ($m != 0) {
                        $detail[] = ['num_compte' => $l['num_compte'], 'montant' => $m];
                    }
                }
            }
        }

        return [
            'total' => $total,
            'detail' => collect($detail)->groupBy('num_compte')->map(fn ($g) => [
                'num_compte' => $g->first()['num_compte'],
                'montant' => round($g->sum('montant'), 2),
            ])->values()->all(),
            'periode' => compact('dateDebut', 'dateFin'),
            'devise' => $devise,
        ];
    }

    public function tvaDeductible(int $societeId, Exercice $exercice, string $dateDebut, string $dateFin, string $devise = 'CDF', string $mode = 'origine'): array
    {
        $prefixes = config('fiscalite.prefixes_tva_deductible', ['445']);
        $lignes = $this->mouvementsPeriode($societeId, $exercice->id, $dateDebut, $dateFin, $devise, $mode);
        $total = $this->sommePrefixesMouvements($lignes, $prefixes, 'debit');

        $detail = [];
        foreach ($lignes as $l) {
            foreach ($prefixes as $p) {
                if (str_starts_with($l['num_compte'], $p)) {
                    $m = round($l['debit'] - $l['credit'], 2);
                    if ($m != 0) {
                        $detail[] = ['num_compte' => $l['num_compte'], 'montant' => $m];
                    }
                }
            }
        }

        return [
            'total' => $total,
            'detail' => collect($detail)->groupBy('num_compte')->map(fn ($g) => [
                'num_compte' => $g->first()['num_compte'],
                'montant' => round($g->sum('montant'), 2),
            ])->values()->all(),
            'periode' => compact('dateDebut', 'dateFin'),
            'devise' => $devise,
        ];
    }

    public function syntheseTva(int $societeId, Exercice $exercice, string $dateDebut, string $dateFin, string $devise = 'CDF', string $mode = 'origine'): array
    {
        $col = $this->tvaCollectee($societeId, $exercice, $dateDebut, $dateFin, $devise, $mode);
        $ded = $this->tvaDeductible($societeId, $exercice, $dateDebut, $dateFin, $devise, $mode);
        $nette = round($col['total'] - $ded['total'], 2);

        return [
            'tva_collectee' => $col['total'],
            'tva_deductible' => $ded['total'],
            'tva_nette' => $nette,
            'credit_tva' => $nette < 0 ? abs($nette) : 0,
            'tva_a_payer' => $nette > 0 ? $nette : 0,
            'collectee_detail' => $col['detail'],
            'deductible_detail' => $ded['detail'],
            'devise' => $devise,
        ];
    }

    public function impotSocietes(int $societeId, Exercice $exercice, string $dateFin, string $devise = 'CDF', string $mode = 'origine'): array
    {
        $cr = $this->etats->compteResultat($societeId, $exercice, $dateFin, $devise, $mode);
        $resultat = 0.0;
        foreach ($cr['lignes'] as $l) {
            if (($l['ref'] ?? '') === 'XG') {
                $resultat = (float) ($l['montant_n'] ?? 0);
                break;
            }
        }
        $taux = (float) config('fiscalite.taux_is', 30);
        $base = max(0, $resultat);
        $impot = round($base * $taux / 100, 2);

        return [
            'resultat_comptable' => round($resultat, 2),
            'base_imposable' => $base,
            'taux_is' => $taux,
            'montant_is' => $impot,
            'devise' => $devise,
        ];
    }

    public function dsf(int $societeId, Exercice $exercice, string $dateFin, string $devise = 'CDF', string $mode = 'origine'): array
    {
        $n1 = $this->etats->exercicePrecedent($societeId, $exercice);
        $bilan = $this->etats->bilan($societeId, $exercice, $dateFin, $devise, $mode, $n1);
        $cr = $this->etats->compteResultat($societeId, $exercice, $dateFin, $devise, $mode, $n1);
        $tva = $this->syntheseTva(
            $societeId,
            $exercice,
            $exercice->date_debut->format('Y-m-d'),
            $dateFin,
            $devise,
            $mode
        );

        return [
            'exercice' => $exercice->libelle,
            'date_arrete' => $dateFin,
            'bilan_total_actif' => $this->extraireRefBilan($bilan, 'TA'),
            'bilan_total_passif' => $this->extraireRefBilan($bilan, 'TP'),
            'bilan_total_capitaux_propres' => (float) ($bilan['total_capitaux_propres'] ?? 0),
            'bilan_total_passif_et_equity' => (float) ($bilan['total_passif'] ?? 0),
            'resultat_net' => $this->extraireRefCr($cr, 'XI'),
            'chiffre_affaires' => $this->extraireRefCr($cr, 'XB'),
            'tva' => $tva,
            'devise' => $devise,
        ];
    }

    protected function extraireRefBilan(array $bilan, string $ref): float
    {
        foreach (array_merge($bilan['actif'] ?? [], $bilan['passif'] ?? []) as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['net_n'] ?? 0);
            }
        }

        return 0.0;
    }

    protected function extraireRefCr(array $cr, string $ref): float
    {
        foreach ($cr['lignes'] ?? [] as $l) {
            if (($l['ref'] ?? '') === $ref) {
                return (float) ($l['montant_n'] ?? 0);
            }
        }

        return 0.0;
    }

    public function echeances(int $societeId, ?Exercice $exercice = null): array
    {
        $exercice = $exercice ?? $this->exerciceCourant($societeId);
        if (! $exercice) {
            return [];
        }

        $saved = DeclarationFiscale::where('societe_id', $societeId)
            ->where('exercice_id', $exercice->id)
            ->orderBy('date_limite_depot')
            ->get();

        $generees = $this->genererEcheancesCalendrier($exercice);
        $items = collect($generees)->map(function ($e) use ($saved) {
            $match = $saved->first(fn ($d) => $d->type === $e['type']
                && $d->periode_debut->format('Y-m-d') === $e['periode_debut']
                && $d->periode_fin->format('Y-m-d') === $e['periode_fin']);

            return array_merge($e, [
                'statut' => $match?->statut ?? 'a_declarer',
                'declaration_id' => $match?->id,
                'date_depot_effectif' => $match?->date_depot_effectif?->format('Y-m-d'),
            ]);
        });

        return $items->sortBy('date_limite_depot')->values()->all();
    }

    protected function genererEcheancesCalendrier(Exercice $exercice): array
    {
        $items = [];
        $debut = Carbon::parse($exercice->date_debut);
        $fin = Carbon::parse($exercice->date_fin);
        $cursor = $debut->copy()->startOfMonth();

        while ($cursor <= $fin) {
            $pDebut = $cursor->copy()->startOfMonth();
            $pFin = $cursor->copy()->endOfMonth();
            if ($pFin > $fin) {
                $pFin = $fin->copy();
            }
            $limite = $pFin->copy()->addDays(15);
            $items[] = [
                'type' => 'tva_mensuelle',
                'libelle' => 'TVA — '.$pDebut->translatedFormat('F Y'),
                'periode_debut' => $pDebut->format('Y-m-d'),
                'periode_fin' => $pFin->format('Y-m-d'),
                'date_limite_depot' => $limite->format('Y-m-d'),
            ];
            $cursor->addMonth();
        }

        $items[] = [
            'type' => 'is',
            'libelle' => 'Impôt sur les sociétés — '.$exercice->libelle,
            'periode_debut' => $exercice->date_debut->format('Y-m-d'),
            'periode_fin' => $exercice->date_fin->format('Y-m-d'),
            'date_limite_depot' => $fin->copy()->addMonths(4)->endOfMonth()->format('Y-m-d'),
        ];
        $items[] = [
            'type' => 'dsf',
            'libelle' => 'DSF — '.$exercice->libelle,
            'periode_debut' => $exercice->date_debut->format('Y-m-d'),
            'periode_fin' => $exercice->date_fin->format('Y-m-d'),
            'date_limite_depot' => $fin->copy()->addMonths(5)->endOfMonth()->format('Y-m-d'),
        ];

        return $items;
    }

    public function enregistrerDeclaration(int $societeId, array $data, ?int $userId = null): DeclarationFiscale
    {
        $tvaNette = ($data['tva_collectee'] ?? 0) - ($data['tva_deductible'] ?? 0);

        return DeclarationFiscale::updateOrCreate(
            [
                'societe_id' => $societeId,
                'exercice_id' => $data['exercice_id'],
                'type' => $data['type'],
                'periode_debut' => $data['periode_debut'],
                'periode_fin' => $data['periode_fin'],
            ],
            [
                'date_limite_depot' => $data['date_limite_depot'] ?? $data['periode_fin'],
                'date_depot_effectif' => $data['date_depot_effectif'] ?? null,
                'base_imposable' => $data['base_imposable'] ?? 0,
                'tva_collectee' => $data['tva_collectee'] ?? 0,
                'tva_deductible' => $data['tva_deductible'] ?? 0,
                'tva_nette' => $tvaNette,
                'montant_impot' => $data['montant_impot'] ?? 0,
                'statut' => $data['statut'] ?? 'brouillon',
                'notes' => $data['notes'] ?? null,
                'etabli_par' => $userId,
            ]
        );
    }

    public function genererDeclarationsPeriode(
        int $societeId,
        Exercice $exercice,
        string $dateDebut,
        string $dateFin,
        string $devise = 'CDF',
        string $mode = 'origine',
        ?int $userId = null
    ): array {
        $tva = $this->syntheseTva($societeId, $exercice, $dateDebut, $dateFin, $devise, $mode);
        $is = $this->impotSocietes($societeId, $exercice, $dateFin, $devise, $mode);

        $declTva = $this->enregistrerDeclaration($societeId, [
            'exercice_id' => $exercice->id,
            'type' => 'tva_mensuelle',
            'periode_debut' => $dateDebut,
            'periode_fin' => $dateFin,
            'tva_collectee' => $tva['tva_collectee'],
            'tva_deductible' => $tva['tva_deductible'],
            'montant_impot' => $tva['tva_a_payer'],
            'statut' => 'brouillon',
        ], $userId);

        $declIs = $this->enregistrerDeclaration($societeId, [
            'exercice_id' => $exercice->id,
            'type' => 'is',
            'periode_debut' => $exercice->date_debut->format('Y-m-d'),
            'periode_fin' => $dateFin,
            'base_imposable' => $is['base_imposable'],
            'montant_impot' => $is['montant_is'],
            'statut' => 'brouillon',
        ], $userId);

        return ['tva' => $declTva, 'is' => $declIs, 'synthese' => $tva, 'is_calcul' => $is];
    }
}
