<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use App\Models\Societe;
use App\Services\FiscaliteService;
use App\Services\LivresComptablesService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscaliteController extends Controller
{
    public function __construct(
        protected FiscaliteService $fiscalite,
        protected LivresComptablesService $livres
    ) {}

    public function tvaCollectee(): View
    {
        return view('fiscalite.tva-collectee', ['page' => 'tva-collectee', 'title' => 'TVA collectée']);
    }

    public function tvaDeductible(): View
    {
        return view('fiscalite.tva-deductible', ['page' => 'tva-deductible', 'title' => 'TVA déductible']);
    }

    public function dsf(): View
    {
        return view('fiscalite.dsf', ['page' => 'dsf', 'title' => 'DSF']);
    }

    public function impotSocietes(): View
    {
        return view('fiscalite.is', ['page' => 'is', 'title' => 'Impôt sur les sociétés']);
    }

    public function declarations(): View
    {
        return view('fiscalite.declarations', ['page' => 'declarations', 'title' => 'Génération déclarations']);
    }

    public function echeances(): View
    {
        return view('fiscalite.echeances', ['page' => 'echeances', 'title' => 'Suivi des échéances']);
    }

    public function metadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->fiscalite->exerciceCourant($societeId);
        $options = $this->livres->optionsDefaut($societe);

        return response()->json([
            'status' => 'success',
            'societe' => $societe,
            'exercice' => $exercice,
            'exercices' => Exercice::where('societe_id', $societeId)->orderByDesc('date_debut')->get(['id', 'libelle', 'date_debut', 'date_fin']),
            'options' => $options,
            'config' => [
                'taux_tva' => config('fiscalite.taux_tva_normal'),
                'taux_is' => config('fiscalite.taux_is'),
            ],
            'date_debut' => $exercice?->date_debut?->format('Y-m-d'),
            'date_fin' => $exercice?->date_fin?->format('Y-m-d'),
        ]);
    }

    protected function ctx(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->fiscalite->exerciceCourant($societeId);
        if ($request->filled('exercice_id')) {
            $exercice = \App\Models\Exercice::where('societe_id', $societeId)->findOrFail($request->integer('exercice_id'));
        }
        if (! $exercice) {
            return ['error' => 'Aucun exercice courant.'];
        }
        $options = $this->livres->optionsDefaut($societe);
        $dateDebut = $request->get('date_debut', $exercice->date_debut->format('Y-m-d'));
        $dateFin = $request->get('date_fin', $exercice->date_fin->format('Y-m-d'));
        $devise = strtoupper($request->get('devise_affichage', $options['devise_affichage']));
        $mode = $request->get('mode_conversion', $options['mode_conversion']);

        return compact('societe', 'exercice', 'dateDebut', 'dateFin', 'devise', 'mode');
    }

    public function apiTvaCollectee(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->ctx($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->fiscalite->tvaCollectee($societeId, $ctx['exercice'], $ctx['dateDebut'], $ctx['dateFin'], $ctx['devise'], $ctx['mode']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function apiTvaDeductible(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->ctx($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->fiscalite->tvaDeductible($societeId, $ctx['exercice'], $ctx['dateDebut'], $ctx['dateFin'], $ctx['devise'], $ctx['mode']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function apiDsf(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->ctx($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->fiscalite->dsf($societeId, $ctx['exercice'], $ctx['dateFin'], $ctx['devise'], $ctx['mode']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function apiIs(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->ctx($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->fiscalite->impotSocietes($societeId, $ctx['exercice'], $ctx['dateFin'], $ctx['devise'], $ctx['mode']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function apiEcheances(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $exercice = $this->fiscalite->exerciceCourant($societeId);

        return response()->json([
            'status' => 'success',
            'echeances' => $this->fiscalite->echeances($societeId, $exercice),
        ]);
    }

    public function apiGenererDeclarations(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->ctx($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $result = $this->fiscalite->genererDeclarationsPeriode(
            $societeId,
            $ctx['exercice'],
            $ctx['dateDebut'],
            $ctx['dateFin'],
            $ctx['devise'],
            $ctx['mode'],
            auth()->id()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Déclarations préparées en brouillon.',
            'data' => $result,
        ]);
    }

    public function apiDeclarationsList(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $exercice = $this->fiscalite->exerciceCourant($societeId);
        $query = \App\Models\DeclarationFiscale::where('societe_id', $societeId)
            ->orderByDesc('periode_fin');
        if ($exercice) {
            $query->where('exercice_id', $exercice->id);
        }

        return response()->json([
            'status' => 'success',
            'declarations' => $query->get(),
        ]);
    }

    public function apiMarquerDeposee(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'declaration_id' => 'required|exists:declarations_fiscales,id',
            'date_depot_effectif' => 'nullable|date',
        ]);

        $decl = \App\Models\DeclarationFiscale::where('societe_id', $societeId)->findOrFail($data['declaration_id']);
        $decl->update([
            'statut' => 'deposee',
            'date_depot_effectif' => $data['date_depot_effectif'] ?? now()->toDateString(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Déclaration marquée comme déposée.', 'declaration' => $decl]);
    }
}
