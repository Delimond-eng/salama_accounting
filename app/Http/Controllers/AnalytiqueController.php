<?php

namespace App\Http\Controllers;

use App\Models\AxeAnalytique;
use App\Models\Journal;
use App\Models\PlanComptable;
use App\Models\PlanComptableAxe;
use App\Models\SectionAnalytique;
use App\Models\Societe;
use App\Services\AnalytiqueComptableService;
use App\Services\LivresComptablesService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalytiqueController extends Controller
{
    public function __construct(
        protected AnalytiqueComptableService $analytique,
        protected LivresComptablesService $livres
    ) {}

    public function axes(): View
    {
        return view('analytique.axes', ['title' => 'Axes & comptes analytiques']);
    }

    public function balance(): View
    {
        return view('analytique.balance', ['title' => 'Balance analytique', 'page' => 'balance']);
    }

    public function grandLivre(): View
    {
        return view('analytique.grand-livre', ['title' => 'Grand livre analytique', 'page' => 'grand-livre']);
    }

    public function rentabilite(): View
    {
        return view('analytique.rentabilite', ['title' => 'Rentabilité projets', 'page' => 'rentabilite']);
    }

    public function centresCout(): View
    {
        return view('analytique.centres-cout', ['title' => 'Centres de coût', 'page' => 'centres-cout']);
    }

    public function dashboard(): View
    {
        return view('analytique.dashboard', ['title' => 'Tableau de bord analytique', 'page' => 'dashboard']);
    }

    public function metadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);

        return response()->json([
            'status' => 'success',
            'exercice' => $exercice,
            'axes' => $this->analytique->axesActifs($societeId),
            'journaux' => Journal::where('societe_id', $societeId)->orderBy('code')->get(['id', 'code', 'libelle']),
            'analytique_axes_restreints' => $this->analytique->axesRestreintsActifs($societe),
            'options' => $this->livres->optionsDefaut($societe),
            'date_debut' => $exercice?->date_debut?->format('Y-m-d'),
            'date_fin' => $exercice?->date_fin?->format('Y-m-d'),
        ]);
    }

    public function axesAll(): JsonResponse
    {
        $societeId = SocieteContext::requireId();

        return response()->json([
            'status' => 'success',
            'axes' => AxeAnalytique::parSociete($societeId)
                ->with(['sections' => fn ($q) => $q->orderByDesc('id')])
                ->orderByDesc('id')->get(),
            'analytique_axes_restreints' => $this->analytique->axesRestreintsActifs(Societe::findOrFail($societeId)),
        ]);
    }

    public function axeSave(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'id' => 'nullable|integer',
            'code' => 'required|string|max:20',
            'libelle' => 'required|string|max:150',
            'description' => 'nullable|string',
            'actif' => 'boolean',
            'ordre_affichage' => 'integer',
        ]);

        $payload = array_merge($data, ['societe_id' => $societeId]);
        unset($payload['id']);

        if (! empty($data['id'])) {
            $axe = AxeAnalytique::parSociete($societeId)->findOrFail($data['id']);
            $axe->update($payload);
        } else {
            $axe = AxeAnalytique::create($payload);
        }

        return response()->json(['status' => 'success', 'message' => 'Axe enregistré.', 'axe' => $axe->fresh()]);
    }

    public function sectionSave(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'id' => 'nullable|integer',
            'axe_analytique_id' => 'required|exists:axes_analytiques,id',
            'code' => 'required|string|max:30',
            'libelle' => 'required|string|max:150',
            'budget' => 'nullable|numeric',
            'actif' => 'boolean',
        ]);

        AxeAnalytique::parSociete($societeId)->findOrFail($data['axe_analytique_id']);

        $payload = array_merge($data, ['societe_id' => $societeId]);
        unset($payload['id']);

        if (! empty($data['id'])) {
            $section = SectionAnalytique::parSociete($societeId)->findOrFail($data['id']);
            $section->update($payload);
        } else {
            $section = SectionAnalytique::create($payload);
        }

        return response()->json(['status' => 'success', 'message' => 'Compte analytique enregistré.', 'section' => $section->fresh('axe')]);
    }

    public function configSave(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        $data = $request->validate(['analytique_axes_restreints' => 'required|boolean']);
        $this->analytique->setAxesRestreints($societe, (bool) $data['analytique_axes_restreints']);

        return response()->json(['status' => 'success', 'message' => 'Configuration enregistrée.']);
    }

    public function compteAxesSave(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'plan_comptable_id' => 'required|exists:plan_comptable,id',
            'axe_ids' => 'array',
            'axe_ids.*' => 'integer|exists:axes_analytiques,id',
            'exige_analytique' => 'boolean',
        ]);

        $compte = PlanComptable::parSociete($societeId)->findOrFail($data['plan_comptable_id']);
        $compte->update(['exige_analytique' => (bool) ($data['exige_analytique'] ?? false)]);

        PlanComptableAxe::where('plan_comptable_id', $compte->id)->delete();
        foreach ($data['axe_ids'] ?? [] as $axeId) {
            PlanComptableAxe::create([
                'societe_id' => $societeId,
                'plan_comptable_id' => $compte->id,
                'axe_analytique_id' => $axeId,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Liaison compte / axes enregistrée.']);
    }

    public function sectionsSearch(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $sections = $this->analytique->rechercherSections(
            $societeId,
            trim((string) $request->get('q', '')),
            $request->integer('axe_id') ?: null,
            $request->get('num_compte')
        );

        return response()->json(['status' => 'success', 'sections' => $sections]);
    }

    protected function filtres(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);
        $deviseOpts = $this->livres->resoudreFiltresDevise($societe, [
            'mode_devise' => $request->get('mode_devise'), 'devise_affichage' => $request->get('devise_affichage'),
            'mode_conversion' => $request->get('mode_conversion'),
            'scope_devise' => $request->get('scope_devise'),
        ]);

        return [
            'exercice_id' => $exercice?->id,
            'date_debut' => $request->get('date_debut', $exercice?->date_debut?->format('Y-m-d')),
            'date_fin' => $request->get('date_fin', $exercice?->date_fin?->format('Y-m-d')),
            'axe_id' => $request->integer('axe_id') ?: null,
            'section_id' => $request->integer('section_id') ?: null,
            'journal_id' => $request->integer('journal_id') ?: null,
            'params_devise' => [
                'mode_devise' => $deviseOpts['mode_devise'], 'devise_affichage' => $deviseOpts['devise_affichage'],
                'mode_conversion' => $deviseOpts['mode_conversion'],
                'scope_devise' => $deviseOpts['scope_devise'],
            ],
        ];
    }

    public function balanceData(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        if (! $f['exercice_id']) {
            return response()->json(['status' => 'success', 'data' => ['items' => []]]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->analytique->balanceAnalytique(
                $societeId,
                $f['exercice_id'],
                $f['date_debut'],
                $f['date_fin'],
                $f['axe_id'],
                $f['section_id'],
                $f['journal_id'],
                $f['params_devise']
            ),
        ]);
    }

    public function grandLivreData(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);

        return response()->json([
            'status' => 'success',
            'data' => $this->analytique->grandLivreAnalytique(
                $societeId,
                $f['exercice_id'],
                $f['date_debut'],
                $f['date_fin'],
                $f['section_id'],
                $f['journal_id'],
                $f['params_devise']
            ),
        ]);
    }

    public function rentabiliteData(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);

        return response()->json([
            'status' => 'success',
            'data' => $this->analytique->rentabiliteProjets(
                $societeId,
                $f['exercice_id'],
                $f['date_debut'],
                $f['date_fin'],
                $f['axe_id'],
                $f['params_devise']
            ),
        ]);
    }

    public function centresCoutData(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);

        return response()->json([
            'status' => 'success',
            'data' => $this->analytique->depensesParAxe(
                $societeId,
                $f['exercice_id'],
                $f['date_debut'],
                $f['date_fin'],
                $f['axe_id'],
                $f['params_devise']
            ),
        ]);
    }

    public function dashboardData(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);

        return response()->json([
            'status' => 'success',
            'data' => $this->analytique->dashboard(
                $societeId,
                $f['exercice_id'],
                $f['date_debut'],
                $f['date_fin'],
                $f['params_devise']
            ),
        ]);
    }
}
