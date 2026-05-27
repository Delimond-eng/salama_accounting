<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TacheService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TacheController extends Controller
{
    public function __construct(
        protected TacheService $taches
    ) {}

    public function index(): View
    {
        return view('taches.index', ['title' => 'Gestion des tâches']);
    }

    public function metadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $user = Auth::user();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return response()->json([
            'status' => 'success',
            'utilisateur' => [
                'id' => $user->id,
                'name' => $user->name,
                'est_super_admin' => $user->estSuperAdmin(),
            ],
            'users' => $users,
            'filtre_defaut' => $user->estSuperAdmin() ? 'toutes' : 'mes_taches',
        ]);
    }

    public function liste(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $user = Auth::user();
        $items = $this->taches->listePourUser($societeId, $user);

        return response()->json(['status' => 'success', 'taches' => $items]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $tache = $this->taches->detail($societeId, $id, Auth::user());

            return response()->json(['status' => 'success', 'tache' => $tache]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 403);
        }
    }

    public function save(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'id' => 'nullable|integer',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_echeance' => 'nullable|date',
                'statut' => 'nullable|in:ouverte,en_cours,terminee,annulee',
                'etapes' => 'required|array|min:1',
                'etapes.*.user_id' => 'required|integer|exists:users,id',
                'etapes.*.libelle' => 'required|string|max:500',
                'etapes.*.ordre' => 'nullable|integer|min:1',
            ]);

            $tache = $this->taches->enregistrer($societeId, $data, $data['id'] ?? null);
            $tache->setAttribute('progression', $tache->progression());

            return response()->json([
                'status' => 'success',
                'message' => empty($data['id']) ? 'Tâche créée.' : 'Tâche mise à jour.',
                'tache' => $tache,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function toggleEtape(int $etapeId): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $etape = $this->taches->basculerEtape($societeId, $etapeId, Auth::user());
            $tache = $this->taches->detail($societeId, $etape->tache_id, Auth::user());

            return response()->json([
                'status' => 'success',
                'etape' => $etape,
                'tache' => $tache,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function rapport(Request $request, int $id): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate(['contenu' => 'required|string']);
            $rapport = $this->taches->ajouterRapport($societeId, $id, $data['contenu'], Auth::user());
            $rapport->load('auteur:id,name');

            return response()->json([
                'status' => 'success',
                'message' => 'Rapport ajouté.',
                'rapport' => $rapport,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function fichier(Request $request, int $id): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $request->validate([
                'fichier' => 'required|file|max:10240',
                'rapport_id' => 'nullable|integer',
            ]);
            $fichier = $this->taches->ajouterFichier(
                $societeId,
                $id,
                $request->file('fichier'),
                Auth::user(),
                $request->integer('rapport_id') ?: null
            );
            $fichier->load('auteur:id,name');

            return response()->json([
                'status' => 'success',
                'message' => 'Fichier joint.',
                'fichier' => $fichier,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function downloadFichier(int $id): BinaryFileResponse|JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $dl = $this->taches->telechargerFichier($societeId, $id, Auth::user());

            return response()->download($dl['path'], $dl['nom'], ['Content-Type' => $dl['mime'] ?? 'application/octet-stream']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 404);
        }
    }
}
