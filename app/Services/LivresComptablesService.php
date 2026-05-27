<?php

namespace App\Services;

use App\Models\Exercice;
use App\Models\PlanComptable;
use App\Models\Societe;
use App\Models\Tiers;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LivresComptablesService
{
    public function __construct(
        protected DeviseConversionService $devises
    ) {}

    public function optionsDefaut(Societe $societe): array
    {
        $params = $societe->parametres ?? [];

        return [
            'devise_principale' => $societe->devise_principale ?? 'CDF',
            'devise_affichage' => $params['devise_affichage'] ?? ($societe->devise_principale ?? 'CDF'),
            'mode_conversion' => $params['mode_conversion'] ?? 'origine',
            'scope_devise' => $params['scope_devise'] ?? 'consolide',
            'devises' => $this->devises->devisesPourAffichage(),
        ];
    }

    /**
     * Fusionne les préférences société avec des filtres explicites (dashboard, livres, etc.).
     *
     * @param  array{devise_affichage?: string, scope_devise?: string, mode_conversion?: string}|null  $filtres
     */
    public function resoudreFiltresDevise(Societe $societe, ?array $filtres = null): array
    {
        $options = $this->optionsDefaut($societe);

        if (! $filtres) {
            return $options;
        }

        if (! empty($filtres['devise_affichage'])) {
            $options['devise_affichage'] = strtoupper((string) $filtres['devise_affichage']);
        }
        if (! empty($filtres['scope_devise']) && in_array($filtres['scope_devise'], ['natif', 'consolide'], true)) {
            $options['scope_devise'] = $filtres['scope_devise'];
        }
        if (! empty($filtres['mode_conversion']) && in_array($filtres['mode_conversion'], ['origine', 'actuel'], true)) {
            $options['mode_conversion'] = $filtres['mode_conversion'];
        }

        return $options;
    }

    protected function baseLignesQuery(int $societeId, int $exerciceId, string $scopeDevise = 'consolide', ?string $deviseAffichage = null)
    {
        $query = DB::table('lignes_ecritures as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->leftJoin('plan_comptable as pc', 'pc.id', '=', 'l.compte_id')
            ->where('l.societe_id', $societeId)
            ->where('l.exercice_id', $exerciceId)
            ->where('e.statut', 'validee')
            ->whereNull('e.deleted_at');

        if ($scopeDevise === 'natif' && $deviseAffichage) {
            $query->where('e.devise', strtoupper($deviseAffichage));
        }

        return $query;
    }

    protected function convertDebitCredit(
        float $debit,
        float $credit,
        string $deviseEcriture,
        float $tauxEcriture,
        string $deviseAffichage,
        int $societeId,
        string $dateEcriture,
        string $mode
    ): array {
        return [
            'debit' => $this->devises->convertir($debit, $deviseEcriture, $deviseAffichage, $tauxEcriture, $societeId, $dateEcriture, $mode),
            'credit' => $this->devises->convertir($credit, $deviseEcriture, $deviseAffichage, $tauxEcriture, $societeId, $dateEcriture, $mode),
        ];
    }

    protected function formatDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    protected function libelleDevise(string $code): string
    {
        $code = strtoupper($code);

        return match ($code) {
            'CDF' => 'Fr',
            'USD' => 'USD',
            default => $code,
        };
    }

    protected function partenaireLigne(?string $tiersNom, ?string $libelleLigne): string
    {
        if ($tiersNom) {
            return $tiersNom;
        }

        return $libelleLigne ?? '';
    }

    protected function splitSolde(float $debit, float $credit): array
    {
        $net = round($debit - $credit, 2);
        if ($net >= 0) {
            return ['debiteur' => $net, 'crediteur' => 0.0];
        }

        return ['debiteur' => 0.0, 'crediteur' => abs($net)];
    }

    protected function agregerParCompte(
        Collection $lignes,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        int $societeId,
        string $mode
    ): Collection {
        $comptes = [];

        foreach ($lignes as $l) {
            $key = $l->num_compte;
            if (! isset($comptes[$key])) {
                $comptes[$key] = [
                    'num_compte' => $l->num_compte,
                    'libelle' => $l->libelle_compte ?? $l->num_compte,
                    'ouv_debit' => 0,
                    'ouv_credit' => 0,
                    'mov_debit' => 0,
                    'mov_credit' => 0,
                ];
            }

            $conv = $this->convertDebitCredit(
                (float) $l->debit,
                (float) $l->credit,
                strtoupper($l->devise_ecriture ?? 'CDF'),
                (float) ($l->taux_change ?? 1),
                $deviseAffichage,
                $societeId,
                $l->date_ecriture,
                $mode
            );

            if ($l->date_ecriture < $dateDebut) {
                $comptes[$key]['ouv_debit'] += $conv['debit'];
                $comptes[$key]['ouv_credit'] += $conv['credit'];
            } elseif ($l->date_ecriture <= $dateFin) {
                $comptes[$key]['mov_debit'] += $conv['debit'];
                $comptes[$key]['mov_credit'] += $conv['credit'];
            }
        }

        return collect($comptes)->map(function ($row) {
            $ouv = $this->splitSolde($row['ouv_debit'], $row['ouv_credit']);
            $finDebit = $row['ouv_debit'] + $row['mov_debit'];
            $finCredit = $row['ouv_credit'] + $row['mov_credit'];
            $fin = $this->splitSolde($finDebit, $finCredit);

            return array_merge($row, [
                'solde_debut_debiteur' => $ouv['debiteur'],
                'solde_debut_crediteur' => $ouv['crediteur'],
                'mouvement_debit' => round($row['mov_debit'], 2),
                'mouvement_credit' => round($row['mov_credit'], 2),
                'solde_fin_debiteur' => $fin['debiteur'],
                'solde_fin_crediteur' => $fin['crediteur'],
            ]);
        })->sortBy('num_compte')->values();
    }

    public function balanceGenerale(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        ?int $classe = null,
        string $scopeDevise = 'consolide'
    ): array {
        $societe = Societe::findOrFail($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        $query = $this->baseLignesQuery($societeId, $exerciceId, $scopeDevise, $deviseAffichage)
            ->where('e.date_ecriture', '<=', $dateFin)
            ->select([
                'l.num_compte',
                'pc.libelle as libelle_compte',
                'l.debit',
                'l.credit',
                'e.date_ecriture',
                'e.devise as devise_ecriture',
                'e.taux_change',
            ]);

        if ($classe) {
            $query->where('pc.classe', $classe);
        }

        $lignes = collect($query->get());
        $lignes = $lignes->filter(fn ($l) => $l->date_ecriture <= $dateFin);

        $rows = $this->agregerParCompte($lignes, $dateDebut, $dateFin, $deviseAffichage, $societeId, $modeConversion);

        $totaux = [
            'solde_debut_debiteur' => $rows->sum('solde_debut_debiteur'),
            'solde_debut_crediteur' => $rows->sum('solde_debut_crediteur'),
            'mouvement_debit' => $rows->sum('mouvement_debit'),
            'mouvement_credit' => $rows->sum('mouvement_credit'),
            'solde_fin_debiteur' => $rows->sum('solde_fin_debiteur'),
            'solde_fin_crediteur' => $rows->sum('solde_fin_crediteur'),
        ];

        return [
            'lignes' => $rows,
            'totaux' => $totaux,
            'devise_affichage' => $deviseAffichage,
            'mode_conversion' => $modeConversion,
            'scope_devise' => $scopeDevise,
        ];
    }

    public function journalGeneral(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        ?int $journalId = null,
        string $scopeDevise = 'consolide'
    ): Collection {
        $societe = Societe::findOrFail($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        $query = $this->baseLignesQuery($societeId, $exerciceId, $scopeDevise, $deviseAffichage)
            ->join('journaux as j', 'j.id', '=', 'l.journal_id')
            ->leftJoin('tiers as t', 't.id', '=', 'l.tiers_id')
            ->whereBetween('e.date_ecriture', [$dateDebut, $dateFin])
            ->orderBy('e.created_at')
            ->orderBy('e.num_piece')
            ->orderBy('l.ordre')
            ->select([
                'e.created_at',
                'e.date_ecriture',
                'e.num_piece',
                'j.code as journal_code',
                'l.num_compte',
                'pc.libelle as libelle_compte',
                'l.libelle',
                'l.debit',
                'l.credit',
                'e.devise as devise_ecriture',
                'e.taux_change',
                't.nom as tiers_nom',
            ]);

        if ($journalId) {
            $query->where('l.journal_id', $journalId);
        }

        return collect($query->get())->map(function ($l) {
            $devise = strtoupper($l->devise_ecriture ?? 'CDF');

            return [
                'date_enregistrement' => $this->formatDateTime($l->created_at),
                'date_ecriture' => $l->date_ecriture,
                'num_piece' => $l->num_piece,
                'journal_code' => $l->journal_code,
                'num_compte' => $l->num_compte,
                'libelle_compte' => $l->libelle_compte,
                'libelle' => $l->libelle,
                'partenaire' => $this->partenaireLigne($l->tiers_nom ?? null, $l->libelle),
                'debit' => round((float) $l->debit, 2),
                'credit' => round((float) $l->credit, 2),
                'devise_saisie' => $devise,
                'devise_libelle' => $this->libelleDevise($devise),
                'taux_change' => (float) ($l->taux_change ?? 1),
            ];
        });
    }

    public function grandLivre(
        int $societeId,
        int $exerciceId,
        string $numCompte,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        string $scopeDevise = 'consolide'
    ): array {
        $societe = Societe::findOrFail($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        $compte = PlanComptable::query()->parSociete($societeId)->where('num_compte', $numCompte)->first();

        $lignesOuverture = $this->baseLignesQuery($societeId, $exerciceId, $scopeDevise, $deviseAffichage)
            ->where('l.num_compte', $numCompte)
            ->where('e.date_ecriture', '<', $dateDebut)
            ->select(['l.debit', 'l.credit', 'e.date_ecriture', 'e.devise as devise_ecriture', 'e.taux_change'])
            ->get();

        $soldeOuv = 0.0;
        foreach ($lignesOuverture as $l) {
            $conv = $this->convertDebitCredit(
                (float) $l->debit,
                (float) $l->credit,
                strtoupper($l->devise_ecriture ?? 'CDF'),
                (float) ($l->taux_change ?? 1),
                $deviseAffichage,
                $societeId,
                $l->date_ecriture,
                $modeConversion
            );
            $soldeOuv += $conv['debit'] - $conv['credit'];
        }

        $mouvements = $this->baseLignesQuery($societeId, $exerciceId, $scopeDevise, $deviseAffichage)
            ->join('journaux as j', 'j.id', '=', 'l.journal_id')
            ->leftJoin('tiers as t', 't.id', '=', 'l.tiers_id')
            ->where('l.num_compte', $numCompte)
            ->whereBetween('e.date_ecriture', [$dateDebut, $dateFin])
            ->orderBy('e.created_at')
            ->orderBy('e.num_piece')
            ->select([
                'e.created_at',
                'e.date_ecriture',
                'e.num_piece',
                'j.code as journal_code',
                'l.libelle',
                'l.debit',
                'l.credit',
                'e.devise as devise_ecriture',
                'e.taux_change',
                'l.lettre',
                't.nom as tiers_nom',
            ])
            ->get();

        $solde = $soldeOuv;
        $lignes = [];
        foreach ($mouvements as $l) {
            $devise = strtoupper($l->devise_ecriture ?? 'CDF');
            $debitOrig = round((float) $l->debit, 2);
            $creditOrig = round((float) $l->credit, 2);
            $conv = $this->convertDebitCredit(
                $debitOrig,
                $creditOrig,
                $devise,
                (float) ($l->taux_change ?? 1),
                $deviseAffichage,
                $societeId,
                $l->date_ecriture,
                $modeConversion
            );
            $solde += $conv['debit'] - $conv['credit'];
            $lignes[] = [
                'date_enregistrement' => $this->formatDateTime($l->created_at),
                'date_ecriture' => $l->date_ecriture,
                'num_piece' => $l->num_piece,
                'journal_code' => $l->journal_code,
                'libelle' => $l->libelle,
                'partenaire' => $this->partenaireLigne($l->tiers_nom ?? null, $l->libelle),
                'debit' => $debitOrig,
                'credit' => $creditOrig,
                'solde' => round($solde, 2),
                'lettre' => $l->lettre,
                'devise_saisie' => $devise,
                'devise_libelle' => $this->libelleDevise($devise),
                'taux_change' => (float) ($l->taux_change ?? 1),
            ];
        }

        return [
            'compte' => $compte,
            'solde_ouverture' => round($soldeOuv, 2),
            'lignes' => $lignes,
            'solde_cloture' => round($solde, 2),
            'devise_affichage' => $deviseAffichage,
            'devise_libelle' => $this->libelleDevise($deviseAffichage),
        ];
    }

    public function grandLivreGeneral(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        string $scopeDevise = 'consolide'
    ): array {
        $balance = $this->balanceGenerale($societeId, $exerciceId, $dateDebut, $dateFin, $deviseAffichage, $modeConversion, null, $scopeDevise);
        $lignes = collect($balance['lignes'])->filter(function ($row) {
            return ($row['mouvement_debit'] ?? 0) != 0
                || ($row['mouvement_credit'] ?? 0) != 0;
        })->map(function ($row) use ($deviseAffichage) {
            $debit = round((float) $row['mouvement_debit'], 2);
            $credit = round((float) $row['mouvement_credit'], 2);

            return [
                'type' => 'compte',
                'num_compte' => $row['num_compte'],
                'libelle' => $row['libelle'],
                'libelle_complet' => trim($row['num_compte'].' '.($row['libelle'] ?? '')),
                'partenaire' => '',
                'devise_saisie' => $deviseAffichage,
                'devise_libelle' => $this->libelleDevise($deviseAffichage),
                'debit' => $debit,
                'credit' => $credit,
                'solde' => round($debit - $credit, 2),
            ];
        })->values();

        $totaux = [
            'debit' => round($lignes->sum('debit'), 2),
            'credit' => round($lignes->sum('credit'), 2),
            'solde' => round($lignes->sum('debit') - $lignes->sum('credit'), 2),
        ];

        return [
            'lignes' => $lignes,
            'totaux' => $totaux,
            'devise_affichage' => $deviseAffichage,
            'devise_libelle' => $this->libelleDevise($deviseAffichage),
        ];
    }

    public function balanceAuxiliaire(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        ?string $typeTiers = null
    ): Collection {
        $societe = Societe::findOrFail($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        $query = $this->baseLignesQuery($societeId, $exerciceId)
            ->join('tiers as t', 't.id', '=', 'l.tiers_id')
            ->whereNotNull('l.tiers_id')
            ->where('e.date_ecriture', '<=', $dateFin)
            ->select([
                't.id as tiers_id',
                't.code as tiers_code',
                't.nom as tiers_nom',
                't.type as tiers_type',
                'l.debit',
                'l.credit',
                'e.date_ecriture',
                'e.devise as devise_ecriture',
                'e.taux_change',
            ]);

        if ($typeTiers) {
            $query->where('t.type', $typeTiers);
        }

        $raw = collect($query->get());
        $grouped = $raw->groupBy('tiers_id');

        return $grouped->map(function ($items, $tiersId) use ($dateDebut, $dateFin, $deviseAffichage, $societeId, $modeConversion) {
            $first = $items->first();
            $ouvD = $ouvC = $movD = $movC = 0;

            foreach ($items as $l) {
                $conv = $this->convertDebitCredit(
                    (float) $l->debit,
                    (float) $l->credit,
                    strtoupper($l->devise_ecriture ?? 'CDF'),
                    (float) ($l->taux_change ?? 1),
                    $deviseAffichage,
                    $societeId,
                    $l->date_ecriture,
                    $modeConversion
                );
                if ($l->date_ecriture < $dateDebut) {
                    $ouvD += $conv['debit'];
                    $ouvC += $conv['credit'];
                } else {
                    $movD += $conv['debit'];
                    $movC += $conv['credit'];
                }
            }

            $ouv = $this->splitSolde($ouvD, $ouvC);
            $fin = $this->splitSolde($ouvD + $movD, $ouvC + $movC);

            return [
                'tiers_id' => $tiersId,
                'code' => $first->tiers_code,
                'nom' => $first->tiers_nom,
                'type' => $first->tiers_type,
                'solde_debut_debiteur' => $ouv['debiteur'],
                'solde_debut_crediteur' => $ouv['crediteur'],
                'mouvement_debit' => round($movD, 2),
                'mouvement_credit' => round($movC, 2),
                'solde_fin_debiteur' => $fin['debiteur'],
                'solde_fin_crediteur' => $fin['crediteur'],
            ];
        })->sortBy('nom')->values();
    }

    public function lettrageNonLettre(int $societeId, string $numCompte, ?int $tiersId = null): Collection
    {
        $query = DB::table('lignes_ecritures as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->leftJoin('tiers as t', 't.id', '=', 'l.tiers_id')
            ->where('l.societe_id', $societeId)
            ->where('e.statut', 'validee')
            ->whereNull('e.deleted_at')
            ->whereNull('l.lettre')
            ->when($numCompte, fn ($q) => $q->where('l.num_compte', 'like', $numCompte.'%'))
            ->orderByDesc('e.created_at')
            ->select([
                'l.id',
                'l.num_compte',
                'l.debit',
                'l.credit',
                'l.libelle',
                'e.created_at',
                'e.date_ecriture',
                'e.num_piece',
                'e.devise',
                'e.taux_change',
                't.code as tiers_code',
                't.nom as tiers_nom',
            ]);

        if ($tiersId) {
            $query->where('l.tiers_id', $tiersId);
        }

        return collect($query->get())->map(function ($l) {
            $devise = strtoupper($l->devise ?? 'CDF');

            return [
                'id' => $l->id,
                'num_compte' => $l->num_compte,
                'debit' => round((float) $l->debit, 2),
                'credit' => round((float) $l->credit, 2),
                'libelle' => $l->libelle,
                'date_enregistrement' => $this->formatDateTime($l->created_at),
                'date_ecriture' => $l->date_ecriture,
                'num_piece' => $l->num_piece,
                'devise_saisie' => $devise,
                'devise_libelle' => $this->libelleDevise($devise),
                'taux_change' => (float) ($l->taux_change ?? 1),
                'tiers_code' => $l->tiers_code,
                'tiers_nom' => $l->tiers_nom,
                'partenaire' => $this->partenaireLigne($l->tiers_nom ?? null, $l->libelle),
            ];
        });
    }

    public function comptesTiers(int $societeId): Collection
    {
        return Tiers::where('societe_id', $societeId)->actif()->orderBy('nom')->get(['id', 'code', 'nom', 'type', 'num_compte_collectif']);
    }

    public function exerciceCourant(int $societeId): ?Exercice
    {
        return Exercice::where('societe_id', $societeId)->where('est_courant', true)->first();
    }

    /** @return array<int, string> */
    public function prefixesTresorerie(string $type): array
    {
        return match ($type) {
            'banque' => ['52'],
            'caisse' => ['57'],
            default => ['52', '57'],
        };
    }

    public function comptesTresorerie(int $societeId, string $type): Collection
    {
        $prefixes = $this->prefixesTresorerie($type);

        return PlanComptable::query()
            ->parSociete($societeId)
            ->actif()
            ->where('classe', 5)
            ->where(function ($q) use ($prefixes): void {
                foreach ($prefixes as $prefix) {
                    $q->orWhere('num_compte', 'like', $prefix.'%');
                }
            })
            ->where(function ($q): void {
                $q->where('est_compte_detail', true)->orWhere('est_rapprochable', true);
            })
            ->orderBy('num_compte')
            ->get(['id', 'num_compte', 'libelle', 'est_rapprochable']);
    }

    /**
     * Livre de trésorerie (banque ou caisse) : mouvements + soldes jour / période / actuel.
     */
    public function livreTresorerie(
        int $societeId,
        int $exerciceId,
        string $numCompte,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        string $type = 'banque'
    ): array {
        $grandLivre = $this->grandLivre(
            $societeId,
            $exerciceId,
            $numCompte,
            $dateDebut,
            $dateFin,
            $deviseAffichage,
            $modeConversion
        );

        $today = now()->toDateString();
        $exercice = Exercice::findOrFail($exerciceId);
        $dateActuel = min($today, $exercice->date_fin->format('Y-m-d'), $dateFin);

        $soldeOuvertureJour = $this->soldeCompteAvantDate(
            $societeId,
            $exerciceId,
            $numCompte,
            $dateDebut,
            $deviseAffichage,
            $modeConversion
        );

        $soldeActuel = $this->soldeCompteAuDate(
            $societeId,
            $exerciceId,
            $numCompte,
            $dateActuel,
            $deviseAffichage,
            $modeConversion
        );

        return array_merge($grandLivre, [
            'type' => $type,
            'soldes' => [
                'ouverture_jour' => round($soldeOuvertureJour, 2),
                'final_periode' => $grandLivre['solde_cloture'],
                'actuel' => round($soldeActuel, 2),
                'date_actuel' => $dateActuel,
            ],
        ]);
    }

    /**
     * Synthèse de tous les comptes de trésorerie d'un type avec solde actuel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function syntheseTresorerie(
        int $societeId,
        int $exerciceId,
        string $type,
        string $dateReference,
        string $deviseAffichage,
        string $modeConversion
    ): array {
        $comptes = $this->comptesTresorerie($societeId, $type);

        return $comptes->map(function ($compte) use ($societeId, $exerciceId, $dateReference, $deviseAffichage, $modeConversion) {
            $solde = $this->soldeCompteAuDate(
                $societeId,
                $exerciceId,
                $compte->num_compte,
                $dateReference,
                $deviseAffichage,
                $modeConversion
            );

            return [
                'id' => $compte->id,
                'num_compte' => $compte->num_compte,
                'libelle' => $compte->libelle,
                'solde_actuel' => round($solde, 2),
            ];
        })->values()->all();
    }

    public function soldeCompteAvantDate(
        int $societeId,
        int $exerciceId,
        string $numCompte,
        string $date,
        string $deviseAffichage,
        string $modeConversion
    ): float {
        return $this->calculerSoldeCompte($societeId, $exerciceId, $numCompte, null, $date, false, $deviseAffichage, $modeConversion);
    }

    public function soldeCompteAuDate(
        int $societeId,
        int $exerciceId,
        string $numCompte,
        string $date,
        string $deviseAffichage,
        string $modeConversion,
        string $scopeDevise = 'consolide'
    ): float {
        return $this->calculerSoldeCompte($societeId, $exerciceId, $numCompte, null, $date, true, $deviseAffichage, $modeConversion, $scopeDevise);
    }

    protected function calculerSoldeCompte(
        int $societeId,
        int $exerciceId,
        string $numCompte,
        ?string $dateMin,
        string $dateMax,
        bool $inclusifMax,
        string $deviseAffichage,
        string $modeConversion,
        string $scopeDevise = 'consolide'
    ): float {
        $societe = Societe::findOrFail($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        $query = $this->baseLignesQuery($societeId, $exerciceId, $scopeDevise, $deviseAffichage)
            ->where('l.num_compte', $numCompte);

        if ($dateMin) {
            $query->where('e.date_ecriture', '>=', $dateMin);
        }

        if ($inclusifMax) {
            $query->where('e.date_ecriture', '<=', $dateMax);
        } else {
            $query->where('e.date_ecriture', '<', $dateMax);
        }

        $lignes = $query->select(['l.debit', 'l.credit', 'e.date_ecriture', 'e.devise as devise_ecriture', 'e.taux_change'])->get();

        $solde = 0.0;
        foreach ($lignes as $l) {
            $conv = $this->convertDebitCredit(
                (float) $l->debit,
                (float) $l->credit,
                strtoupper($l->devise_ecriture ?? 'CDF'),
                (float) ($l->taux_change ?? 1),
                $deviseAffichage,
                $societeId,
                $l->date_ecriture,
                $modeConversion
            );
            $solde += $conv['debit'] - $conv['credit'];
        }

        return $solde;
    }

    /**
     * Somme des mouvements sur un préfixe de compte (période) avec filtre natif ou consolidation.
     *
     * @param  string  $sens  produit (crédit−débit), charge (débit−crédit), net (crédit−débit)
     */
    public function sommeFluxPeriode(
        int $societeId,
        int $exerciceId,
        string $prefixCompte,
        string $dateDebut,
        string $dateFin,
        string $deviseAffichage,
        string $modeConversion,
        string $scopeDevise = 'consolide',
        string $sens = 'produit'
    ): float {
        $societe = Societe::findOrFail($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        $lignes = $this->baseLignesQuery($societeId, $exerciceId, $scopeDevise, $deviseAffichage)
            ->where('l.num_compte', 'like', $prefixCompte.'%')
            ->whereBetween('e.date_ecriture', [$dateDebut, $dateFin])
            ->select(['l.debit', 'l.credit', 'e.date_ecriture', 'e.devise as devise_ecriture', 'e.taux_change'])
            ->get();

        $total = 0.0;
        foreach ($lignes as $l) {
            $conv = $this->convertDebitCredit(
                (float) $l->debit,
                (float) $l->credit,
                strtoupper($l->devise_ecriture ?? 'CDF'),
                (float) ($l->taux_change ?? 1),
                $deviseAffichage,
                $societeId,
                $l->date_ecriture,
                $modeConversion
            );
            $total += match ($sens) {
                'charge' => $conv['debit'] - $conv['credit'],
                'net' => $conv['credit'] - $conv['debit'],
                default => $conv['credit'] - $conv['debit'],
            };
        }

        return round($total, 2);
    }
}
