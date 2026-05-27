<?php

namespace App\Services;

use App\Exceptions\BilanDesequilibreException;
use App\Models\Ecriture;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\Societe;
use App\Models\TauxChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardComptableService
{
    public function __construct(
        protected LivresComptablesService $livres,
        protected BilanComptableService $bilan,
        protected FluxTresorerieService $tft,
        protected DeviseConversionService $devises
    ) {}

    /**
     * @param  array{devise_affichage?: string, scope_devise?: string, mode_conversion?: string}|null  $filtresDevise
     */
    public function assembler(int $societeId, ?array $filtresDevise = null): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);
        $options = $this->livres->resoudreFiltresDevise($societe, $filtresDevise);
        $devise = $options['devise_affichage'];
        $mode = $options['mode_conversion'];
        $scope = $options['scope_devise'] ?? 'consolide';
        $today = now()->toDateString();

        if (! $exercice) {
            return [
                'societe' => $societe,
                'exercice' => null,
                'devise' => $devise,
                'options_devise' => $options,
                'message' => 'Aucun exercice courant. Configurez une société et un exercice dans Paramètres.',
            ];
        }

        $dateFin = min($today, $exercice->date_fin->format('Y-m-d'));
        $debutMois = Carbon::parse($dateFin)->startOfMonth()->toDateString();
        $debutExercice = $exercice->date_debut->format('Y-m-d');

        $kpis = $this->kpis($societeId, $exercice, $dateFin, $debutMois, $debutExercice, $devise, $mode, $scope);
        $controles = $this->controles($societeId, $exercice, $dateFin, $devise, $mode, $scope);
        $devises = $this->blocDevises($societeId, $exercice->id, $today);
        $alertes = $this->alertes($kpis, $controles, $devises);

        return [
            'societe' => $societe,
            'exercice' => $exercice,
            'devise' => $devise,
            'scope_devise' => $scope,
            'mode_conversion' => $mode,
            'options_devise' => $options,
            'date_reference' => $dateFin,
            'kpis' => $kpis,
            'controles' => $controles,
            'alertes' => $alertes,
            'activite_recente' => $this->activiteRecente($societeId, $exercice->id),
            'graphiques' => $this->graphiques($societeId, $exercice, $dateFin, $devise, $mode, $scope),
            'exercices' => $this->listeExercices($societeId),
            'devises' => $devises,
            'ventes' => $this->blocVentes($societeId, $exercice, $dateFin, $debutMois, $today, $devise, $mode, $scope),
            'liens_rapides' => $this->liensRapides(),
        ];
    }

    protected function kpis(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $debutMois,
        string $debutExercice,
        string $devise,
        string $mode,
        string $scope
    ): array {
        $banque = $this->soldePrefixe($societeId, $exercice->id, '52', $dateFin, $devise, $mode, $scope);
        $caisse = $this->soldePrefixe($societeId, $exercice->id, '57', $dateFin, $devise, $mode, $scope);
        $resultat = $this->bilan->resultatNetExercice($societeId, $exercice, $dateFin, $devise, $mode, $scope);

        return [
            'tresorerie' => [
                'banque' => round($banque, 2),
                'caisse' => round($caisse, 2),
                'total' => round($banque + $caisse, 2),
            ],
            'resultat_exercice' => round($resultat, 2),
            'resultat_positif' => $resultat >= 0,
            'creances_clients' => $this->soldeClassePrefixe($societeId, $exercice->id, '411', $dateFin, $devise, $mode, $scope, 'debit'),
            'dettes_fournisseurs' => $this->soldeClassePrefixe($societeId, $exercice->id, '401', $dateFin, $devise, $mode, $scope, 'credit'),
            'ecritures' => [
                'aujourdhui' => $this->compterEcritures($societeId, $exercice->id, $dateFin, $dateFin),
                'mois' => $this->compterEcritures($societeId, $exercice->id, $debutMois, $dateFin),
                'exercice' => $this->compterEcritures($societeId, $exercice->id, $debutExercice, $dateFin),
            ],
            'journaux' => $this->statsJournaux($societeId, $exercice->id),
        ];
    }

    protected function controles(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $devise,
        string $mode,
        string $scope
    ): array {
        $balance = $this->controleBalance($societeId, $exercice->id, $exercice->date_debut->format('Y-m-d'), $dateFin);
        $bilan = $this->controleBilan($societeId, $exercice, $dateFin, $devise, $mode, $scope);
        $tft = $this->controleTft($societeId, $exercice, $dateFin, $devise, $mode, $scope);
        $anomalies = $this->comptesAnormaux($societeId, $exercice->id, $dateFin, $devise, $mode, $scope);
        $journal = $this->controleJournaux($societeId, $exercice->id);

        return [
            'balance' => $balance,
            'bilan' => $bilan,
            'tft' => $tft,
            'comptes_anormaux' => $anomalies,
            'journal' => $journal,
            'tous_ok' => $balance['ok'] && $bilan['ok'] && $tft['ok'] && $anomalies['items'] === [] && $journal['items'] === [],
        ];
    }

    protected function alertes(array $kpis, array $controles, array $devises): array
    {
        $items = [];

        foreach ($kpis['journaux']['desequilibrees'] ?? [] as $e) {
            $items[] = [
                'niveau' => 'danger',
                'titre' => 'Écriture déséquilibrée',
                'detail' => $e['libelle'],
                'url' => route('accounting.saisie.nouvelle')
            ];
        }
        if (($kpis['journaux']['brouillons_od'] ?? 0) > 0) {
            $items[] = [
                'niveau' => 'warning',
                'titre' => 'OD non validées',
                'detail' => $kpis['journaux']['brouillons_od'].' écriture(s) en brouillon (journal OD).',
                'url' => route('accounting.saisie.od', ['statut' => 'brouillon'])
            ];
        }
        if (($kpis['journaux']['brouillons_total'] ?? 0) > 0) {
            $items[] = [
                'niveau' => 'info',
                'titre' => 'Brouillons en attente',
                'detail' => $kpis['journaux']['brouillons_total'].' écriture(s) à valider.',
                'url' => route('accounting.saisie.nouvelle', ['statut' => 'brouillon'])
            ];
        }
        foreach ($controles['comptes_anormaux']['items'] ?? [] as $a) {
            $items[] = [
                'niveau' => 'warning',
                'titre' => 'Compte anormal',
                'detail' => $a,
                'url' => route('accounting.livres.balance')
            ];
        }
        foreach ($controles['journal']['items'] ?? [] as $j) {
            $items[] = [
                'niveau' => 'warning',
                'titre' => 'Contrôle journal',
                'detail' => $j,
                'url' => route('accounting.livres.journal')
            ];
        }
        if (! ($controles['balance']['ok'] ?? true)) {
            $items[] = [
                'niveau' => 'danger',
                'titre' => 'Balance déséquilibrée',
                'detail' => $controles['balance']['message'],
                'url' => route('accounting.livres.balance')
            ];
        }
        if (! ($controles['bilan']['ok'] ?? true)) {
            $items[] = [
                'niveau' => 'danger',
                'titre' => 'Bilan déséquilibré',
                'detail' => $controles['bilan']['message'],
                'url' => route('accounting.etats.bilan')
            ];
        }
        if (! ($controles['tft']['ok'] ?? true)) {
            $items[] = [
                'niveau' => 'warning',
                'titre' => 'Contrôle TFT',
                'detail' => $controles['tft']['message'],
                'url' => route('accounting.etats.flux-tresorerie')
            ];
        }
        if (($kpis['tresorerie']['caisse'] ?? 0) < 0) {
            $items[] = [
                'niveau' => 'danger',
                'titre' => 'Caisse négative',
                'detail' => 'Le solde caisse (571) est débiteur en trésorerie.',
                'url' => route('accounting.livres.caisse')
            ];
        }
        if (($kpis['tresorerie']['banque'] ?? 0) < 0) {
            $items[] = [
                'niveau' => 'danger',
                'titre' => 'Banque négative',
                'detail' => 'Découvert bancaire détecté sur les comptes 52x.',
                'url' => route('accounting.livres.banque')
            ];
        }
        if (($devises['taux_manquant'] ?? false)) {
            $items[] = [
                'niveau' => 'info',
                'titre' => 'Taux de change',
                'detail' => 'Taux USD/CDF du jour non renseigné.',
                'url' => route('accounting.parametres.devises')
            ];
        }

        return ['items' => $items, 'count' => count($items)];
    }

    protected function activiteRecente(int $societeId, int $exerciceId): array
    {
        return Ecriture::query()
            ->with('journal:id,code,libelle,type')
            ->where('societe_id', $societeId)
            ->where('exercice_id', $exerciceId)
            ->where('statut', 'validee')
            ->orderByDesc('date_ecriture')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'journal_id', 'num_piece', 'date_ecriture', 'libelle', 'total_debit', 'total_credit', 'devise'])
            ->map(fn ($e) => [
                'date' => $e->date_ecriture->format('d/m/Y'),
                'journal_code' => $e->journal?->code ?? '—',
                'journal_type' => $e->journal?->type ?? 'autre',
                'num_piece' => $e->num_piece,
                'libelle' => $e->libelle,
                'montant' => max((float) $e->total_debit, (float) $e->total_credit),
                'devise' => $e->devise,
            ])
            ->all();
    }

    protected function graphiques(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $devise,
        string $mode,
        string $scope
    ): array {
        return [
            'tresorerie_mensuelle' => $this->evolutionTresorerieMensuelle($societeId, $exercice, $dateFin, $devise, $mode, $scope),
            'charges' => $this->repartitionClasse($societeId, $exercice, $dateFin, '6', 6, $devise, $mode, $scope),
            'produits' => $this->repartitionClasse($societeId, $exercice, $dateFin, '7', 6, $devise, $mode, $scope),
            'resultat_mensuel' => $this->resultatMensuel($societeId, $exercice, $dateFin, $devise, $mode, $scope),
        ];
    }

    protected function listeExercices(int $societeId): array
    {
        return Exercice::where('societe_id', $societeId)
            ->orderByDesc('date_debut')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'libelle' => $e->libelle,
                'annee' => $e->annee,
                'statut' => $e->statut,
                'est_courant' => $e->est_courant,
                'date_debut' => $e->date_debut->format('d/m/Y'),
                'date_fin' => $e->date_fin->format('d/m/Y'),
            ])
            ->all();
    }

    protected function blocDevises(int $societeId, int $exerciceId, string $today): array
    {
        $societe = Societe::find($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');
        $tauxUsd = $this->devises->tauxJournalier($societeId, 'USD', $today);
        $tauxEnregistre = TauxChange::where('societe_id', $societeId)
            ->where('devise_code', 'USD')
            ->where('date_taux', $today)
            ->exists();

        $ecrituresDevise = Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exerciceId)
            ->where('statut', 'validee')
            ->where('devise', '!=', $societe->devise_principale ?? 'CDF')
            ->count();

        return [
            'taux_usd_cdf' => $tauxUsd,
            'taux_manquant' => ! $tauxEnregistre && $tauxUsd <= 1,
            'ecritures_multi_devise' => $ecrituresDevise,
            'devise_principale' => $societe->devise_principale ?? 'CDF',
        ];
    }

    protected function blocVentes(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $debutMois,
        string $today,
        string $devise,
        string $mode,
        string $scope
    ): array {
        $caMois = $this->livres->sommeFluxPeriode($societeId, $exercice->id, '70', $debutMois, $dateFin, $devise, $mode, $scope, 'produit');
        $caJour = $this->livres->sommeFluxPeriode($societeId, $exercice->id, '70', $today, $today, $devise, $mode, $scope, 'produit');
        $moisPrec = Carbon::parse($debutMois)->subMonth();
        $caMoisPrec = $this->livres->sommeFluxPeriode(
            $societeId,
            $exercice->id,
            '70',
            $moisPrec->startOfMonth()->toDateString(),
            $moisPrec->endOfMonth()->toDateString(),
            $devise,
            $mode,
            $scope,
            'produit'
        );
        $evolution = $caMoisPrec != 0 ? round((($caMois - $caMoisPrec) / abs($caMoisPrec)) * 100, 1) : null;

        return [
            'ca_mois' => round($caMois, 2),
            'ca_jour' => round($caJour, 2),
            'ca_mois_precedent' => round($caMoisPrec, 2),
            'evolution_pct' => $evolution,
        ];
    }

    protected function liensRapides(): array
    {
        return [
            'Saisie & Opérations' => [
                ['label' => 'Nouvelle écriture', 'route' => 'accounting.saisie.nouvelle', 'icon' => 'ti-plus', 'color' => 'primary'],
                ['label' => 'Saisie Ventes', 'route' => 'accounting.saisie.ventes', 'icon' => 'ti-shopping-cart', 'color' => 'success'],
                ['label' => 'Saisie Caisse', 'route' => 'accounting.saisie.caisse', 'icon' => 'ti-wallet', 'color' => 'info'],
                ['label' => 'Import Relevé', 'route' => 'accounting.saisie.import-releve', 'icon' => 'ti-file-import', 'color' => 'secondary'],
            ],
            'Consultation & Livres' => [
                ['label' => 'Journal général', 'route' => 'accounting.livres.journal', 'icon' => 'ti-notebook', 'color' => 'primary'],
                ['label' => 'Grand livre', 'route' => 'accounting.livres.grand-livre', 'icon' => 'ti-book-2', 'color' => 'info'],
                ['label' => 'Balance générale', 'route' => 'accounting.livres.balance', 'icon' => 'ti-scale', 'color' => 'secondary'],
                ['label' => 'Lettrage', 'route' => 'accounting.livres.lettrage', 'icon' => 'ti-checkup-list', 'color' => 'warning'],
            ],
            'États & Rapports' => [
                ['label' => 'Bilan', 'route' => 'accounting.etats.bilan', 'icon' => 'ti-report-money', 'color' => 'success'],
                ['label' => 'Compte de résultat', 'route' => 'accounting.etats.compte-resultat', 'icon' => 'ti-chart-bar', 'color' => 'success'],
                ['label' => 'Tableau de flux', 'route' => 'accounting.etats.flux-tresorerie', 'icon' => 'ti-arrows-shuffle', 'color' => 'warning'],
                ['label' => 'Exports fiscaux', 'route' => 'accounting.etats.exports', 'icon' => 'ti-download', 'color' => 'dark'],
            ]
        ];
    }

    protected function soldePrefixe(
        int $societeId,
        int $exerciceId,
        string $prefix,
        string $date,
        string $devise,
        string $mode,
        string $scope
    ): float {
        $total = 0.0;
        foreach ($this->livres->comptesTresorerie($societeId, $prefix === '52' ? 'banque' : 'caisse') as $compte) {
            $total += $this->livres->soldeCompteAuDate($societeId, $exerciceId, $compte->num_compte, $date, $devise, $mode, $scope);
        }

        return $total;
    }

    protected function soldeClassePrefixe(
        int $societeId,
        int $exerciceId,
        string $prefix,
        string $dateFin,
        string $devise,
        string $mode,
        string $scope,
        string $sensAttendu
    ): float {
        $balance = $this->livres->balanceGenerale(
            $societeId,
            $exerciceId,
            Exercice::find($exerciceId)->date_debut->format('Y-m-d'),
            $dateFin,
            $devise,
            $mode,
            null,
            $scope
        );

        $total = 0.0;
        foreach ($balance['lignes'] as $row) {
            if (! str_starts_with((string) $row['num_compte'], $prefix)) {
                continue;
            }
            $net = (float) $row['solde_fin_debiteur'] - (float) $row['solde_fin_crediteur'];
            $total += $sensAttendu === 'credit' ? -$net : max(0, $net);
        }

        return round($total, 2);
    }

    protected function compterEcritures(int $societeId, int $exerciceId, string $debut, string $fin): int
    {
        return Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exerciceId)
            ->where('statut', 'validee')
            ->whereBetween('date_ecriture', [$debut, $fin])
            ->count();
    }

    protected function statsJournaux(int $societeId, int $exerciceId): array
    {
        $desequilibrees = Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exerciceId)
            ->whereIn('statut', ['validee', 'brouillon'])
            ->whereRaw('ABS(total_debit - total_credit) >= 0.01')
            ->limit(5)
            ->get(['num_piece', 'libelle', 'total_debit', 'total_credit'])
            ->map(fn ($e) => [
                'libelle' => $e->num_piece.' — '.$e->libelle.' (Δ '.number_format(abs($e->total_debit - $e->total_credit), 2).')',
            ])
            ->all();

        $odJournal = Journal::where('societe_id', $societeId)->where('code', 'OD')->value('id');
        $brouillonsOd = $odJournal
            ? Ecriture::where('societe_id', $societeId)->where('exercice_id', $exerciceId)->where('journal_id', $odJournal)->where('statut', 'brouillon')->count()
            : 0;

        $brouillonsTotal = Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exerciceId)
            ->where('statut', 'brouillon')
            ->count();

        return [
            'desequilibrees' => $desequilibrees,
            'desequilibrees_count' => count($desequilibrees),
            'brouillons_od' => $brouillonsOd,
            'brouillons_total' => $brouillonsTotal,
        ];
    }

    protected function controleBalance(int $societeId, int $exerciceId, string $debut, string $fin): array
    {
        $row = DB::table('lignes_ecritures as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->where('l.societe_id', $societeId)
            ->where('l.exercice_id', $exerciceId)
            ->where('e.statut', 'validee')
            ->whereNull('e.deleted_at')
            ->whereBetween('e.date_ecriture', [$debut, $fin])
            ->selectRaw('COALESCE(SUM(l.debit),0) as d, COALESCE(SUM(l.credit),0) as c')
            ->first();

        $d = round((float) ($row->d ?? 0), 2);
        $c = round((float) ($row->c ?? 0), 2);
        $ok = abs($d - $c) < 0.02;

        return [
            'ok' => $ok,
            'total_debit' => $d,
            'total_credit' => $c,
            'ecart' => round($d - $c, 2),
            'message' => $ok ? 'Équilibrée.' : 'Écart de '.number_format(abs($d - $c), 2),
        ];
    }

    protected function controleBilan(int $societeId, Exercice $exercice, string $dateFin, string $devise, string $mode, string $scope = 'consolide'): array
    {
        try {
            $this->bilan->generer($societeId, $exercice, $dateFin, $devise, $mode, $scope);

            return ['ok' => true, 'message' => 'Actif = Passif.'];
        } catch (BilanDesequilibreException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    protected function controleTft(int $societeId, Exercice $exercice, string $dateFin, string $devise, string $mode, string $scope = 'consolide'): array
    {
        try {
            $tft = $this->tft->generer($societeId, $exercice, $dateFin, $devise, $mode, null, $scope);
            $ok = ($tft['controle']['ok'] ?? false) === true;

            return [
                'ok' => $ok,
                'message' => $ok ? 'TFT valide.' : 'Écart TFT détecté.',
                'detail' => $tft['controle'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'TFT : '.$e->getMessage()];
        }
    }

    protected function comptesAnormaux(int $societeId, int $exerciceId, string $dateFin, string $devise, string $mode, string $scope = 'consolide'): array
    {
        $items = [];
        $exercice = Exercice::find($exerciceId);
        $balance = $this->livres->balanceGenerale(
            $societeId,
            $exerciceId,
            $exercice->date_debut->format('Y-m-d'),
            $dateFin,
            $devise,
            $mode,
            null,
            $scope
        );

        foreach ($balance['lignes'] as $row) {
            $num = (string) $row['num_compte'];
            $net = round((float) $row['solde_fin_debiteur'] - (float) $row['solde_fin_crediteur'], 2);
            if (abs($net) < 0.01) {
                continue;
            }
            if (str_starts_with($num, '401') && $net > 0) {
                $items[] = "{$num} débiteur (".number_format($net, 0).')';
            }
            if (str_starts_with($num, '411') && $net < 0) {
                $items[] = "{$num} créditeur (".number_format(abs($net), 0).')';
            }
            if ((str_starts_with($num, '521') || str_starts_with($num, '571')) && $net < 0) {
                $items[] = "{$num} négative (".number_format(abs($net), 0).')';
            }
        }

        return ['items' => array_slice($items, 0, 5), 'count' => count($items)];
    }

    protected function controleJournaux(int $societeId, int $exerciceId): array
    {
        $items = [];

        $doublons = DB::table('ecritures')
            ->where('societe_id', $societeId)
            ->where('exercice_id', $exerciceId)
            ->where('statut', 'validee')
            ->whereNull('deleted_at')
            ->select('num_piece', DB::raw('COUNT(*) as n'))
            ->groupBy('num_piece')
            ->having('n', '>', 1)
            ->limit(3)
            ->get();

        foreach ($doublons as $d) {
            $items[] = "Doublon : {$d->num_piece}";
        }

        $ex = Exercice::find($exerciceId);
        $horsExercice = $ex
            ? Ecriture::where('societe_id', $societeId)
                ->where('exercice_id', $exerciceId)
                ->where('statut', 'validee')
                ->where(function ($q) use ($ex): void {
                    $q->where('date_ecriture', '<', $ex->date_debut)
                        ->orWhere('date_ecriture', '>', $ex->date_fin);
                })
                ->count()
            : 0;

        if ($horsExercice > 0) {
            $items[] = "{$horsExercice} hors période.";
        }

        return ['items' => $items, 'count' => count($items)];
    }

    protected function evolutionTresorerieMensuelle(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $devise,
        string $mode,
        string $scope
    ): array {
        $labels = [];
        $banque = [];
        $caisse = [];
        $total = [];

        $fin = Carbon::parse($dateFin)->endOfMonth();
        for ($i = 5; $i >= 0; $i--) {
            $m = $fin->copy()->subMonths($i);
            if ($m->lt($exercice->date_debut)) {
                continue;
            }
            $dernier = min($m->copy()->endOfMonth()->toDateString(), $dateFin);
            $labels[] = $m->translatedFormat('M Y');
            $b = $this->soldePrefixe($societeId, $exercice->id, '52', $dernier, $devise, $mode, $scope);
            $c = $this->soldePrefixe($societeId, $exercice->id, '57', $dernier, $devise, $mode, $scope);
            $banque[] = round($b, 2);
            $caisse[] = round($c, 2);
            $total[] = round($b + $c, 2);
        }

        if ($labels === []) {
            $labels[] = Carbon::parse($dateFin)->translatedFormat('M Y');
            $b = $this->soldePrefixe($societeId, $exercice->id, '52', $dateFin, $devise, $mode, $scope);
            $c = $this->soldePrefixe($societeId, $exercice->id, '57', $dateFin, $devise, $mode, $scope);
            $banque[] = round($b, 2);
            $caisse[] = round($c, 2);
            $total[] = round($b + $c, 2);
        }

        return ['labels' => $labels, 'banque' => $banque, 'caisse' => $caisse, 'total' => $total];
    }

    protected function repartitionClasse(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $classe,
        int $top,
        string $devise,
        string $mode,
        string $scope
    ): array {
        $balance = $this->livres->balanceGenerale(
            $societeId,
            $exercice->id,
            $exercice->date_debut->format('Y-m-d'),
            $dateFin,
            $devise,
            $mode,
            (int) $classe,
            $scope
        );

        $rows = collect($balance['lignes'])->map(function ($row) use ($classe) {
            $debit = (float) ($row['mouvement_debit'] ?? 0);
            $credit = (float) ($row['mouvement_credit'] ?? 0);
            $montant = $classe === '6' ? $debit - $credit : $credit - $debit;

            return [
                'libelle' => trim($row['num_compte'].' '.($row['libelle'] ?? '')),
                'montant' => round(max(0, $montant), 2),
            ];
        })
            ->filter(fn ($x) => $x['montant'] > 0)
            ->sortByDesc('montant')
            ->values();

        $topRows = $rows->take($top);
        $autres = $rows->slice($top)->sum('montant');
        $labels = $topRows->pluck('libelle')->all();
        $series = $topRows->pluck('montant')->all();
        if ($autres > 0) {
            $labels[] = 'Autres';
            $series[] = round($autres, 2);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    protected function resultatMensuel(
        int $societeId,
        Exercice $exercice,
        string $dateFin,
        string $devise,
        string $mode,
        string $scope
    ): array {
        $labels = [];
        $series = [];
        $fin = Carbon::parse($dateFin);

        for ($i = 5; $i >= 0; $i--) {
            $m = $fin->copy()->subMonths($i);
            if ($m->lt($exercice->date_debut)) {
                continue;
            }
            $debut = max($m->copy()->startOfMonth()->toDateString(), $exercice->date_debut->format('Y-m-d'));
            $finM = min($m->copy()->endOfMonth()->toDateString(), $dateFin);

            $produits = $this->livres->sommeFluxPeriode($societeId, $exercice->id, '7', $debut, $finM, $devise, $mode, $scope, 'produit');
            $charges = $this->livres->sommeFluxPeriode($societeId, $exercice->id, '6', $debut, $finM, $devise, $mode, $scope, 'charge');

            $labels[] = $m->translatedFormat('M');
            $series[] = round($produits - $charges, 2);
        }

        if ($labels === []) {
            $labels[] = Carbon::parse($dateFin)->translatedFormat('M');
            $series[] = round($this->bilan->resultatNetExercice($societeId, $exercice, $dateFin, $devise, $mode, $scope), 2);
        }

        return ['labels' => $labels, 'series' => $series];
    }
}
