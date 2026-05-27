<?php

namespace App\Services;

use App\Models\AxeAnalytique;
use App\Models\Ecriture;
use App\Models\Journal;
use App\Models\LigneEcriture;
use App\Models\LigneEcritureAnalytique;
use App\Models\PlanComptable;
use App\Models\PlanComptableAxe;
use App\Models\SectionAnalytique;
use App\Models\Societe;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AnalytiqueComptableService
{
    public function __construct(
        protected DeviseConversionService $devises
    ) {}

    public function axesRestreintsActifs(Societe $societe): bool
    {
        $params = $societe->parametres ?? [];

        return (bool) ($params['analytique_axes_restreints'] ?? false);
    }

    public function setAxesRestreints(Societe $societe, bool $actif): void
    {
        $params = $societe->parametres ?? [];
        $params['analytique_axes_restreints'] = $actif;
        $societe->update(['parametres' => $params]);
    }

    /**
     * @return Collection<int, AxeAnalytique>
     */
    public function axesActifs(int $societeId): Collection
    {
        return AxeAnalytique::parSociete($societeId)->actif()
            ->orderBy('ordre_affichage')->orderBy('code')
            ->with(['sections' => fn ($q) => $q->actif()->orderBy('code')])
            ->get();
    }

    /**
     * Axes autorisés pour un compte (si restriction activée).
     */
    public function axesAutorisesPourCompte(int $societeId, PlanComptable $compte, Societe $societe): Collection
    {
        if (! $this->axesRestreintsActifs($societe)) {
            return $this->axesActifs($societeId);
        }

        $axeIds = PlanComptableAxe::where('societe_id', $societeId)
            ->where('plan_comptable_id', $compte->id)
            ->pluck('axe_analytique_id');

        if ($axeIds->isEmpty()) {
            return $this->axesActifs($societeId);
        }

        return AxeAnalytique::parSociete($societeId)->actif()
            ->whereIn('id', $axeIds)
            ->orderBy('ordre_affichage')
            ->get();
    }

    public function ligneExigeAnalytique(Journal $journal, PlanComptable $compte): bool
    {
        if ((bool) $compte->exige_analytique) {
            return true;
        }
        if (! $journal->analytique_obligatoire) {
            return false;
        }
        $classe = (int) substr(preg_replace('/\D/', '', $compte->num_compte), 0, 1);

        return in_array($classe, [2, 6, 7], true);
    }

    /**
     * @param  array<int, array{section_analytique_id?: int, analytiques?: array<int, array{section_analytique_id: int}>}>  $lignes
     */
    public function validerLignesAnalytiques(int $societeId, Journal $journal, array $lignes, Societe $societe): void
    {
        foreach ($lignes as $i => $ligne) {
            $debit = (float) ($ligne['debit'] ?? 0);
            $credit = (float) ($ligne['credit'] ?? 0);
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $num = preg_replace('/\s+/', '', (string) ($ligne['num_compte'] ?? ''));
            if ($num === '') {
                continue;
            }

            $compte = PlanComptable::parSociete($societeId)->where('num_compte', $num)->first();
            if (! $compte) {
                continue;
            }

            if (! $this->ligneExigeAnalytique($journal, $compte)) {
                continue;
            }

            $sections = $this->extraireSectionsLigne($ligne);
            if ($sections === []) {
                throw new InvalidArgumentException(
                    'Ligne '.($i + 1)." ({$num}) : un compte analytique est obligatoire pour ce journal/compte."
                );
            }

            foreach ($sections as $sectionId) {
                $this->validerSectionPourCompte($societeId, $compte, $sectionId, $societe, $i + 1, $num);
            }
        }
    }

    /**
     * @return array<int, int> section IDs
     */
    public function extraireSectionsLigne(array $ligne): array
    {
        $ids = [];
        $sectionId = $ligne['section_analytique_id'] ?? $ligne['sectionAnalytiqueId'] ?? null;
        if ($sectionId !== null && $sectionId !== '' && ! is_nan((float) $sectionId) && (int) $sectionId > 0) {
            $ids[] = (int) $sectionId;
        }
        foreach ($ligne['analytiques'] ?? [] as $a) {
            if (! empty($a['section_analytique_id'])) {
                $ids[] = (int) $a['section_analytique_id'];
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    protected function validerSectionPourCompte(
        int $societeId,
        PlanComptable $compte,
        int $sectionId,
        Societe $societe,
        int $numLigne,
        string $numCompte
    ): void {
        $section = SectionAnalytique::parSociete($societeId)->actif()->with('axe')->find($sectionId);
        if (! $section) {
            throw new InvalidArgumentException("Ligne {$numLigne} : compte analytique invalide.");
        }

        if (! $this->axesRestreintsActifs($societe)) {
            return;
        }

        $autorises = $this->axesAutorisesPourCompte($societeId, $compte, $societe)->pluck('id');
        if ($autorises->isNotEmpty() && ! $autorises->contains($section->axe_analytique_id)) {
            throw new InvalidArgumentException(
                "Ligne {$numLigne} ({$numCompte}) : l'axe « {$section->axe->libelle} » n'est pas autorisé pour ce compte."
            );
        }
    }

    /**
     * Synchronise les écritures analytiques après enregistrement des lignes.
     *
     * @param  array<int, LigneEcriture>  $lignesModeles  index => LigneEcriture
     * @param  array<int, array>  $lignesData
     */
    public function synchroniserEcriture(
        Ecriture $ecriture,
        array $lignesModeles,
        array $lignesData
    ): void {
        LigneEcritureAnalytique::where('ecriture_id', $ecriture->id)->delete();

        foreach ($lignesData as $i => $ligneData) {
            $ligne = $lignesModeles[$i] ?? null;
            if (! $ligne) {
                continue;
            }

            $sectionIds = $this->extraireSectionsLigne($ligneData);
            if ($sectionIds === []) {
                $ligne->update(['axe_analytique_id' => null, 'section_analytique_id' => null]);

                continue;
            }

            $montantLigne = max((float) $ligne->debit, (float) $ligne->credit);
            $primarySection = null;

            foreach ($sectionIds as $sectionId) {
                $section = SectionAnalytique::with('axe')->findOrFail($sectionId);
                if (! $primarySection) {
                    $primarySection = $section;
                }

                LigneEcritureAnalytique::create([
                    'ligne_ecriture_id' => $ligne->id,
                    'ecriture_id' => $ecriture->id,
                    'societe_id' => $ecriture->societe_id,
                    'exercice_id' => $ecriture->exercice_id,
                    'journal_id' => $ecriture->journal_id,
                    'axe_analytique_id' => $section->axe_analytique_id,
                    'section_analytique_id' => $section->id,
                    'montant' => $montantLigne,
                    'pourcentage' => 100,
                ]);
            }

            if ($primarySection) {
                $ligne->update([
                    'axe_analytique_id' => $primarySection->axe_analytique_id,
                    'section_analytique_id' => $primarySection->id,
                ]);
            }
        }
    }

    public function supprimerParEcriture(int $ecritureId): void
    {
        LigneEcritureAnalytique::where('ecriture_id', $ecritureId)->delete();
    }

    /**
     * @param  array{devise_affichage?: string, mode_conversion?: string, scope_devise?: string}  $paramsDevise
     */
    public function balanceAnalytique(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        ?int $axeId = null,
        ?int $sectionId = null,
        ?int $journalId = null,
        array $paramsDevise = []
    ): array {
        $ctx = $this->parametresDevise($societeId, $paramsDevise);
        $agregats = $this->agregerMouvementsParSection(
            $societeId,
            $exerciceId,
            $dateDebut,
            $dateFin,
            $axeId,
            $sectionId,
            $journalId,
            $ctx
        );

        $liste = $this->sectionsPourRapport($societeId, $axeId, $sectionId)
            ->map(function (SectionAnalytique $section) use ($agregats) {
                $m = $agregats[$section->id] ?? ['debit' => 0.0, 'credit' => 0.0];

                return [
                    'section_id' => $section->id,
                    'section_code' => $section->code,
                    'section_libelle' => $section->libelle,
                    'axe_code' => $section->axe->code ?? '',
                    'axe_libelle' => $section->axe->libelle ?? '',
                    'debit' => round($m['debit'], 2),
                    'credit' => round($m['credit'], 2),
                    'solde' => round($m['debit'] - $m['credit'], 2),
                ];
            })
            ->sortBy(['axe_code', 'section_code'])
            ->values()
            ->all();

        return [
            'items' => $liste,
            'devise' => $ctx['devise_affichage'],
            'totaux' => [
                'debit' => round(collect($liste)->sum('debit'), 2),
                'credit' => round(collect($liste)->sum('credit'), 2),
            ],
        ];
    }

  /**
     * @param  array{devise_affichage?: string, mode_conversion?: string, scope_devise?: string}  $paramsDevise
     */
    public function grandLivreAnalytique(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        ?int $sectionId = null,
        ?int $journalId = null,
        array $paramsDevise = []
    ): array {
        $ctx = $this->parametresDevise($societeId, $paramsDevise);
        $rows = $this->baseLignesAnalytiquesQuery($societeId, $exerciceId, $dateDebut, $dateFin, null, $sectionId, $journalId, $ctx)
            ->leftJoin('journaux as j', 'j.id', '=', 'e.journal_id')
            ->orderBy('l.date_ecriture')
            ->orderBy('e.num_piece')
            ->select([
                'l.date_ecriture',
                'e.num_piece',
                'e.libelle as libelle_ecriture',
                'l.libelle as libelle_ligne',
                'l.num_compte',
                'j.code as journal_code',
                'a.code as axe_code',
                'a.libelle as axe_libelle',
                's.code as section_code',
                's.libelle as section_libelle',
                'l.debit',
                'l.credit',
                'e.devise',
                'e.taux_change',
            ])
            ->get()
            ->map(function ($r) use ($ctx, $societeId) {
                $r->debit = $this->convertirMontant((float) $r->debit, $r, $ctx, $societeId);
                $r->credit = $this->convertirMontant((float) $r->credit, $r, $ctx, $societeId);

                return $r;
            });

        return ['lignes' => $rows, 'devise' => $ctx['devise_affichage']];
    }

  /**
     * @param  array{devise_affichage?: string, mode_conversion?: string, scope_devise?: string}  $paramsDevise
     */
    public function rentabiliteProjets(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        ?int $axeId = null,
        array $paramsDevise = []
    ): array {
        $ctx = $this->parametresDevise($societeId, $paramsDevise);
        $sections = $this->sectionsPourRapport($societeId, $axeId, null);

        $projets = [];
        foreach ($sections as $section) {
            $charges = $this->sommeClasseSection($societeId, $exerciceId, $dateDebut, $dateFin, $section->id, '6', $ctx);
            $produits = $this->sommeClasseSection($societeId, $exerciceId, $dateDebut, $dateFin, $section->id, '7', $ctx);
            $projets[] = [
                'section_id' => $section->id,
                'axe_code' => $section->axe->code ?? '',
                'axe_libelle' => $section->axe->libelle ?? '',
                'code' => $section->code,
                'libelle' => $section->libelle,
                'charges' => round($charges, 2),
                'produits' => round($produits, 2),
                'resultat' => round($produits - $charges, 2),
            ];
        }

        usort($projets, fn ($a, $b) => $b['resultat'] <=> $a['resultat']);

        return [
            'axe' => $axeId ? AxeAnalytique::parSociete($societeId)->find($axeId) : null,
            'projets' => $projets,
            'devise' => $ctx['devise_affichage'],
        ];
    }

  /**
     * @param  array{devise_affichage?: string, mode_conversion?: string, scope_devise?: string}  $paramsDevise
     */
    public function depensesParAxe(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        ?int $axeId = null,
        array $paramsDevise = []
    ): array {
        $ctx = $this->parametresDevise($societeId, $paramsDevise);
        $items = $this->sectionsPourRapport($societeId, $axeId, null)->map(function (SectionAnalytique $section) use (
            $societeId,
            $exerciceId,
            $dateDebut,
            $dateFin,
            $ctx
        ) {
            $depenses = $this->sommeClasseSection($societeId, $exerciceId, $dateDebut, $dateFin, $section->id, '6', $ctx);

            return [
                'section_id' => $section->id,
                'section_code' => $section->code,
                'section_libelle' => $section->libelle,
                'axe_code' => $section->axe->code ?? '',
                'axe_libelle' => $section->axe->libelle ?? '',
                'depenses' => round($depenses, 2),
            ];
        })->sortByDesc('depenses')->values()->all();

        return ['items' => $items, 'devise' => $ctx['devise_affichage']];
    }

    /**
     * @param  array{devise_affichage?: string, mode_conversion?: string, scope_devise?: string}  $paramsDevise
     */
    public function dashboard(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        array $paramsDevise = []
    ): array {
        $rentabilite = $this->rentabiliteProjets($societeId, $exerciceId, $dateDebut, $dateFin, null, $paramsDevise);
        $topCouteux = collect($rentabilite['projets'] ?? [])
            ->sortByDesc('charges')
            ->take(5)
            ->values()
            ->all();

        $ctx = $this->parametresDevise($societeId, $paramsDevise);
        $evolution = $this->evolutionMensuelleCharges($societeId, $exerciceId, $dateDebut, $dateFin, $ctx);

        return [
            'rentabilite' => $rentabilite,
            'top_projets_couteux' => $topCouteux,
            'evolution_charges' => $evolution,
            'depenses_par_axe' => $this->depensesParAxe($societeId, $exerciceId, $dateDebut, $dateFin, null, $paramsDevise),
            'devise' => $ctx['devise_affichage'],
        ];
    }

    protected function sommeClasseSection(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        int $sectionId,
        string $classe,
        array $ctx
    ): float {
        $total = 0.0;
        $rows = $this->baseLignesAnalytiquesQuery($societeId, $exerciceId, $dateDebut, $dateFin, null, $sectionId, null, $ctx)
            ->where('l.num_compte', 'like', $classe.'%')
            ->select(['l.debit', 'l.credit', 'e.devise', 'e.taux_change', 'l.date_ecriture'])
            ->get();

        foreach ($rows as $row) {
            $d = $this->convertirMontant((float) $row->debit, $row, $ctx, $societeId);
            $c = $this->convertirMontant((float) $row->credit, $row, $ctx, $societeId);
            $total += $classe === '6' ? $d - $c : $c - $d;
        }

        return $total;
    }

    protected function evolutionMensuelleCharges(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        array $ctx
    ): array {
        $parMois = [];
        $rows = $this->baseLignesAnalytiquesQuery($societeId, $exerciceId, $dateDebut, $dateFin, null, null, null, $ctx)
            ->where('l.num_compte', 'like', '6%')
            ->select(['l.debit', 'l.credit', 'l.date_ecriture', 'e.devise', 'e.taux_change'])
            ->get();

        foreach ($rows as $row) {
            $periode = Carbon::parse($row->date_ecriture)->format('Y-m');
            $d = $this->convertirMontant((float) $row->debit, $row, $ctx, $societeId);
            $c = $this->convertirMontant((float) $row->credit, $row, $ctx, $societeId);
            $parMois[$periode] = ($parMois[$periode] ?? 0) + $d - $c;
        }
        ksort($parMois);

        return [
            'labels' => array_map(
                fn ($p) => Carbon::createFromFormat('Y-m', $p)->locale('fr')->isoFormat('MMM YYYY'),
                array_keys($parMois)
            ),
            'series' => array_map(fn ($m) => round($m, 2), array_values($parMois)),
        ];
    }

    /**
     * @param  array{devise_affichage: string, mode_conversion: string, scope_devise: string}  $ctx
     * @return array<int, array{debit: float, credit: float}>
     */
    protected function agregerMouvementsParSection(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        ?int $axeId,
        ?int $sectionId,
        ?int $journalId,
        array $ctx
    ): array {
        $items = [];
        $rows = $this->baseLignesAnalytiquesQuery($societeId, $exerciceId, $dateDebut, $dateFin, $axeId, $sectionId, $journalId, $ctx)
            ->select([
                's.id as section_id',
                'l.debit',
                'l.credit',
                'e.devise',
                'e.taux_change',
                'l.date_ecriture',
            ])
            ->get();

        foreach ($rows as $r) {
            $sid = (int) $r->section_id;
            if (! isset($items[$sid])) {
                $items[$sid] = ['debit' => 0.0, 'credit' => 0.0];
            }
            $items[$sid]['debit'] += $this->convertirMontant((float) $r->debit, $r, $ctx, $societeId);
            $items[$sid]['credit'] += $this->convertirMontant((float) $r->credit, $r, $ctx, $societeId);
        }

        return $items;
    }

    protected function sectionsPourRapport(int $societeId, ?int $axeId, ?int $sectionId): Collection
    {
        return SectionAnalytique::parSociete($societeId)->actif()
            ->with('axe:id,code,libelle')
            ->when($axeId, fn ($q) => $q->where('axe_analytique_id', $axeId))
            ->when($sectionId, fn ($q) => $q->where('id', $sectionId))
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  array{devise_affichage?: string, mode_conversion?: string, scope_devise?: string}  $params
     * @return array{devise_affichage: string, mode_conversion: string, scope_devise: string}
     */
    protected function parametresDevise(int $societeId, array $params): array
    {
        $societe = Societe::find($societeId);
        $this->devises->setDevisePrincipale($societe->devise_principale ?? 'CDF');

        return [
            'devise_affichage' => strtoupper($params['devise_affichage'] ?? $societe->devise_principale ?? 'CDF'),
            'mode_conversion' => $params['mode_conversion'] ?? 'origine',
            'scope_devise' => $params['scope_devise'] ?? 'consolide',
        ];
    }

    protected function convertirMontant(float $montant, object $row, array $ctx, int $societeId): float
    {
        if ($montant == 0.0) {
            return 0.0;
        }

        return $this->devises->convertir(
            $montant,
            strtoupper($row->devise ?? 'CDF'),
            $ctx['devise_affichage'],
            (float) ($row->taux_change ?? 1),
            $societeId,
            $row->date_ecriture,
            $ctx['mode_conversion']
        );
    }

    /**
     * @param  array{devise_affichage: string, mode_conversion: string, scope_devise: string}  $ctx
     */
    protected function baseLignesAnalytiquesQuery(
        int $societeId,
        int $exerciceId,
        string $dateDebut,
        string $dateFin,
        ?int $axeId,
        ?int $sectionId,
        ?int $journalId,
        array $ctx
    ) {
        $q = DB::table('lignes_ecritures as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->join('sections_analytiques as s', 's.id', '=', 'l.section_analytique_id')
            ->join('axes_analytiques as a', 'a.id', '=', 's.axe_analytique_id')
            ->where('l.societe_id', $societeId)
            ->where('l.exercice_id', $exerciceId)
            ->where('e.statut', 'validee')
            ->whereNull('e.deleted_at')
            ->whereNotNull('l.section_analytique_id')
            ->whereBetween('l.date_ecriture', [$dateDebut, $dateFin])
            ->when($axeId, fn ($q) => $q->where('a.id', $axeId))
            ->when($sectionId, fn ($q) => $q->where('s.id', $sectionId))
            ->when($journalId, fn ($q) => $q->where('e.journal_id', $journalId));

        if ($ctx['scope_devise'] === 'natif') {
            $q->where('e.devise', $ctx['devise_affichage']);
        }

        return $q;
    }

    public function rechercherSections(int $societeId, string $q, ?int $axeId = null, ?string $numCompte = null): Collection
    {
        $query = SectionAnalytique::parSociete($societeId)->actif()
            ->with('axe:id,code,libelle')
            ->when($axeId, fn ($qb) => $qb->where('axe_analytique_id', $axeId))
            ->when($q !== '', fn ($qb) => $qb->where(fn ($s) => $s
                ->where('code', 'like', "%{$q}%")
                ->orWhere('libelle', 'like', "%{$q}%")));

        if ($numCompte) {
            $compte = PlanComptable::parSociete($societeId)->where('num_compte', $numCompte)->first();
            $societe = Societe::find($societeId);
            if ($compte && $societe && $this->axesRestreintsActifs($societe)) {
                $axeIds = $this->axesAutorisesPourCompte($societeId, $compte, $societe)->pluck('id');
                if ($axeIds->isNotEmpty()) {
                    $query->whereIn('axe_analytique_id', $axeIds);
                }
            }
        }

        return $query->orderBy('code')->limit(30)->get();
    }
}
