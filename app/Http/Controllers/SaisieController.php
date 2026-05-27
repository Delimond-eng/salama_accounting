<?php

namespace App\Http\Controllers;

use App\Models\Ecriture;
use App\Models\Journal;
use App\Models\PlanComptable;
use App\Models\TauxChange;
use App\Models\Tiers;
use App\Services\SaisieComptableService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaisieController extends Controller
{
    public function __construct(
        protected SaisieComptableService $saisie
    ) {}

    public function liste(string $page = 'nouvelle'): View
    {
        return view('saisie.liste', $this->saisie->pageMeta($page));
    }

    public function ecriture(string $page = 'nouvelle', ?int $id = null): View
    {
        return view('saisie.ecriture', array_merge(
            $this->saisie->pageMeta($page),
            ['ecritureId' => $id]
        ));
    }

    public function importReleve(): View
    {
        return view('saisie.import-releve', $this->saisie->pageMeta('import'));
    }

    public function metadata(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $page = $request->get('page', 'nouvelle');
        $journal = $this->saisie->resolveJournal($societeId, $page, $request->integer('journal_id') ?: null);
        $exercice = $this->saisie->exerciceCourant($societeId);
        $societe = SocieteContext::societe();

        $journaux = Journal::where('societe_id', $societeId)->where('actif', true)
            ->orderBy('ordre_affichage')->orderBy('code')->get();

        $meta = $this->saisie->pageMeta($page);
        $multiDevise = ! empty($meta['multi_devise']);
        $today = now()->toDateString();

        return response()->json([
            'status' => 'success',
            'societe' => $societe,
            'exercice' => $exercice,
            'journal' => $journal,
            'journaux' => $journaux,
            'devise_principale' => $societe?->devise_principale ?? 'CDF',
            'multi_devise' => $multiDevise,
            'template' => $journal ? $this->saisie->suggestTemplate($journal) : [],
            'taux_usd' => $this->saisie->tauxPourDevise($societeId, 'USD', $today),
        ]);
    }

    public function ecrituresList(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $page = $request->get('page', 'nouvelle');
        $journal = $this->saisie->resolveJournal($societeId, $page, $request->integer('journal_id') ?: null);

        $query = Ecriture::with(['journal:id,code,libelle,type', 'lignes'])
            ->parSociete($societeId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($journal) {
            $query->where('journal_id', $journal->id);
        }
        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(fn ($q) => $q
                ->where('num_piece', 'like', "%{$search}%")
                ->orWhere('libelle', 'like', "%{$search}%")
                ->orWhere('reference_externe', 'like', "%{$search}%"));
        }
        if ($dateDebut = $request->get('date_debut')) {
            $query->where('date_ecriture', '>=', $dateDebut);
        }
        if ($dateFin = $request->get('date_fin')) {
            $query->where('date_ecriture', '<=', $dateFin);
        }

        $ecritures = $query->limit(200)->get();

        return response()->json(['status' => 'success', 'ecritures' => $ecritures, 'total' => $ecritures->count()]);
    }

    public function ecritureShow(int $id): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ecriture = Ecriture::with(['lignes.tiers', 'lignes.compte', 'journal', 'exercice'])
            ->parSociete($societeId)->findOrFail($id);

        return response()->json(['status' => 'success', 'ecriture' => $ecriture]);
    }

    public function comptesSearch(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $search = trim((string) $request->get('q', ''));

        $comptes = PlanComptable::query()
            ->parSociete($societeId)
            ->actif()
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('num_compte', 'like', "%{$search}%")
                ->orWhere('libelle', 'like', "%{$search}%")))
            ->orderBy('num_compte')
            ->limit(30)
            ->get(['id', 'num_compte', 'libelle', 'est_compte_tiers', 'classe']);

        return response()->json(['status' => 'success', 'comptes' => $comptes]);
    }

    public function tiersSearch(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $search = trim((string) $request->get('q', ''));

        $tiers = Tiers::where('societe_id', $societeId)->actif()
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('code', 'like', "%{$search}%")
                ->orWhere('nom', 'like', "%{$search}%")))
            ->orderBy('nom')->limit(30)
            ->get(['id', 'code', 'nom', 'type', 'num_compte_collectif']);

        return response()->json(['status' => 'success', 'tiers' => $tiers]);
    }

    public function tauxDevise(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'devise' => 'required|string|size:3',
            'date' => 'required|date',
        ]);

        $taux = $this->saisie->tauxPourDevise($societeId, $data['devise'], $data['date']);

        return response()->json(['status' => 'success', 'taux' => $taux]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $this->validateEcritureRequest($request);

            $result = $this->saisie->enregistrer(
                $societeId,
                $data['entete'],
                $data['lignes'],
                (bool) $request->boolean('valider'),
                $data['entete']['id'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => $request->boolean('valider') ? 'Écriture validée.' : 'Brouillon enregistré.',
                'ecriture' => $result['ecriture'],
                'warnings' => $result['warnings'] ?? [],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        } catch (\Throwable $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function validateEcriture(int $id): JsonResponse
    {
        try {
            $ecriture = $this->saisie->validerEcriture(SocieteContext::requireId(), $id);

            return response()->json(['status' => 'success', 'message' => 'Écriture validée.', 'ecriture' => $ecriture]);
        } catch (\Throwable $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->saisie->supprimerBrouillon(SocieteContext::requireId(), $id);

            return response()->json(['status' => 'success', 'message' => 'Écriture supprimée.']);
        } catch (\Throwable $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function importReleveStore(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'journal_id' => 'required|exists:journaux,id',
                'exercice_id' => 'required|exists:exercices,id',
                'csv_content' => 'required|string',
            ]);

            $mouvements = $this->saisie->parseCsvReleve($data['csv_content']);
            $creees = $this->saisie->importerReleveBancaire(
                $societeId,
                (int) $data['journal_id'],
                (int) $data['exercice_id'],
                $mouvements
            );

            return response()->json([
                'status' => 'success',
                'message' => $creees->count().' écriture(s) importée(s) en brouillon.',
                'count' => $creees->count(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    private function validateEcritureRequest(Request $request): array
    {
        $validated = $request->validate([
            'entete' => 'required|array',
            'entete.id' => 'nullable|exists:ecritures,id',
            'entete.exercice_id' => 'required|exists:exercices,id',
            'entete.journal_id' => 'required|exists:journaux,id',
            'entete.date_ecriture' => 'required|date',
            'entete.date_piece' => 'nullable|date',
            'entete.date_valeur' => 'nullable|date',
            'entete.date_echeance' => 'nullable|date',
            'entete.libelle' => 'required|string|max:255',
            'entete.type_ecriture' => 'nullable|in:normale,ouverture,cloture,inventaire,extourne,simulation,budget',
            'entete.reference_externe' => 'nullable|string|max:100',
            'entete.reference_facture' => 'nullable|string|max:100',
            'entete.devise' => 'nullable|string|size:3',
            'entete.taux_change' => 'nullable|numeric|min:0',
            'entete.notes' => 'nullable|string',
            'lignes' => 'required|array|min:2',
            'lignes.*.num_compte' => 'required|string|max:15',
            'lignes.*.libelle' => 'nullable|string|max:255',
            'lignes.*.debit' => 'nullable|numeric|min:0',
            'lignes.*.credit' => 'nullable|numeric|min:0',
            'lignes.*.tiers_id' => 'nullable|exists:tiers,id',
            'lignes.*.devise' => 'nullable|string|size:3',
            'lignes.*.montant_devise' => 'nullable|numeric',
            'lignes.*.taux_change' => 'nullable|numeric|min:0',
        ]);

        return $validated;
    }
}
