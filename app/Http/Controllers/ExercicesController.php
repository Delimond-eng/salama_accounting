<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use App\Models\Societe;
use App\Services\ExerciceComptableService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ExercicesController extends Controller
{
    public function __construct(
        protected ExerciceComptableService $exercices
    ) {}

    public function index(): View
    {
        return view('exercices.index', ['page' => 'index', 'title' => 'Multi-exercices']);
    }

    public function ouverture(): View
    {
        return view('exercices.ouverture', ['page' => 'ouverture', 'title' => 'Ouverture d\'exercice']);
    }

    public function cloture(): View
    {
        return view('exercices.cloture', ['page' => 'cloture', 'title' => 'Clôture comptable']);
    }

    public function reportANouveau(): View
    {
        return view('exercices.report-a-nouveau', ['page' => 'report-a-nouveau', 'title' => 'Report à nouveau']);
    }

    public function metadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        $courant = $this->exercices->exerciceCourant($societeId);
        $liste = $this->exercices->lister($societeId);

        return response()->json([
            'status' => 'success',
            'societe' => $societe,
            'exercice_courant' => $courant,
            'exercices' => $liste,
        ]);
    }

    public function liste(): JsonResponse
    {
        $societeId = SocieteContext::requireId();

        return response()->json([
            'status' => 'success',
            'exercices' => $this->exercices->lister($societeId),
        ]);
    }

    public function controles(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'exercice_id' => 'required|integer',
                'date_fin' => 'nullable|date',
            ]);

            return response()->json([
                'status' => 'success',
                'controles' => $this->exercices->controlesCloture(
                    $societeId,
                    (int) $data['exercice_id'],
                    $data['date_fin'] ?? null
                ),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function controlesMensuels(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'exercice_id' => 'required|integer',
                'annee' => 'required|integer',
                'mois' => 'required|integer|min:1|max:12',
            ]);

            return response()->json([
                'status' => 'success',
                'controles' => $this->exercices->controlesMensuels(
                    $societeId,
                    (int) $data['exercice_id'],
                    (int) $data['annee'],
                    (int) $data['mois']
                ),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function preCloture(Request $request): JsonResponse
    {
        return $this->wrapAction(function () use ($request) {
            $societeId = SocieteContext::requireId();
            $data = $request->validate(['exercice_id' => 'required|integer']);
            $exercice = $this->exercices->passerPreCloture($societeId, (int) $data['exercice_id']);

            return ['message' => 'Exercice en pré-clôture.', 'exercice' => $exercice];
        });
    }

    public function cloturer(Request $request): JsonResponse
    {
        return $this->wrapAction(function () use ($request) {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'exercice_id' => 'required|integer',
                'notes' => 'nullable|string|max:2000',
            ]);
            $result = $this->exercices->cloturerExercice(
                $societeId,
                (int) $data['exercice_id'],
                $data['notes'] ?? null
            );

            return [
                'message' => 'Exercice clôturé avec succès.',
                'exercice' => $result['exercice'],
                'ecriture_cloture' => $result['ecriture_cloture'],
                'resultat_net' => $result['resultat_net'],
            ];
        });
    }

    public function creerSuivant(Request $request): JsonResponse
    {
        return $this->wrapAction(function () use ($request) {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'exercice_source_id' => 'required|integer',
                'est_courant' => 'boolean',
            ]);
            $exercice = $this->exercices->creerExerciceSuivant(
                $societeId,
                (int) $data['exercice_source_id'],
                $data['est_courant'] ?? true
            );

            return ['message' => 'Nouvel exercice créé.', 'exercice' => $exercice];
        });
    }

    public function genererBilanOuverture(Request $request): JsonResponse
    {
        return $this->wrapAction(function () use ($request) {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'exercice_id' => 'required|integer',
                'exercice_source_id' => 'nullable|integer',
            ]);
            $result = $this->exercices->genererBilanOuverture(
                $societeId,
                (int) $data['exercice_id'],
                isset($data['exercice_source_id']) ? (int) $data['exercice_source_id'] : null
            );

            return [
                'message' => 'Bilan d\'ouverture généré ('.$result['nb_lignes'].' lignes).',
                'exercice' => $result['exercice'],
                'ecriture' => $result['ecriture'],
            ];
        });
    }

    public function genererReportANouveau(Request $request): JsonResponse
    {
        return $this->wrapAction(function () use ($request) {
            $societeId = SocieteContext::requireId();
            $data = $request->validate(['exercice_id' => 'required|integer']);
            $result = $this->exercices->genererReportANouveau($societeId, (int) $data['exercice_id']);

            return [
                'message' => 'Report à nouveau généré.',
                'exercice' => $result['exercice'],
                'ecriture' => $result['ecriture'],
            ];
        });
    }

    public function definirCourant(Request $request): JsonResponse
    {
        return $this->wrapAction(function () use ($request) {
            $societeId = SocieteContext::requireId();
            $data = $request->validate(['exercice_id' => 'required|integer']);
            $exercice = $this->exercices->definirExerciceCourant($societeId, (int) $data['exercice_id']);

            return ['message' => 'Exercice courant mis à jour.', 'exercice' => $exercice];
        });
    }

    public function saveExercice(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'id' => 'nullable|exists:exercices,id',
                'libelle' => 'required|string|max:100',
                'annee' => 'required|integer|min:2000|max:2100',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after_or_equal:date_debut',
                'statut' => 'required|in:ouvert,pre_cloture,cloture,archive',
                'est_courant' => 'boolean',
            ]);

            $exercice = null;
            \Illuminate\Support\Facades\DB::transaction(function () use ($data, $societeId, &$exercice) {
                $payload = array_merge($data, ['societe_id' => $societeId]);
                unset($payload['id']);
                if (! empty($data['id'])) {
                    $exercice = Exercice::where('societe_id', $societeId)->findOrFail($data['id']);
                    $exercice->update($payload);
                } else {
                    $exercice = Exercice::create($payload);
                }
                if (! empty($data['est_courant'])) {
                    $this->exercices->definirExerciceCourant($societeId, $exercice->id);
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Exercice enregistré.',
                'exercice' => $exercice->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
    }

    protected function wrapAction(callable $fn): JsonResponse
    {
        try {
            $payload = $fn();

            return response()->json(array_merge(['status' => 'success'], $payload));
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }
}
