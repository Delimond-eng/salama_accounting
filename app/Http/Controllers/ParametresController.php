<?php

namespace App\Http\Controllers;

use App\Models\Devise;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\PlanComptable;
use App\Models\Societe;
use App\Models\SocieteBanque;
use App\Models\TauxChange;
use App\Models\Tiers;
use App\Support\DeviseAffichage;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\View\View;

class ParametresController extends Controller
{
    public function planComptable(): View
    {
        return view('parametres.plan-comptable');
    }

    public function journaux(): View
    {
        return view('parametres.journaux');
    }

    public function devises(): View
    {
        return view('parametres.devises');
    }

    public function tiers(): View
    {
        return view('parametres.tiers');
    }

    public function societe(): View
    {
        return view('parametres.societe');
    }

    public function context(): JsonResponse
    {
        $societe = SocieteContext::societe();

        return response()->json([
            'status' => 'success',
            'societe_id' => $societe?->id,
            'societe' => $societe,
            'exercice_courant' => $societe?->exercices()->where('est_courant', true)->first(),
            'societes' => Societe::orderBy('raison_sociale')->get(['id', 'code', 'raison_sociale', 'devise_principale']),
            'options_devise' => DeviseAffichage::options($societe),
        ]);
    }

    public function deviseOptions(): JsonResponse
    {
        $societe = SocieteContext::societe();

        return response()->json([
            'status' => 'success',
            'options' => DeviseAffichage::options($societe),
        ]);
    }

    public function selectSociete(Request $request): JsonResponse
    {
        $data = $request->validate(['societe_id' => 'required|exists:societes,id']);
        SocieteContext::set((int) $data['societe_id']);

        return response()->json(['status' => 'success', 'message' => 'Société active mise à jour.', 'societe' => Societe::find($data['societe_id'])]);
    }

    public function planComptableAll(Request $request): JsonResponse
    {
        $societeId = SocieteContext::id();
        $classe = $request->integer('classe') ?: null;
        $search = trim((string) $request->get('search', ''));

        $query = PlanComptable::query()
            ->when($societeId, fn ($q) => $q->parSociete($societeId), fn ($q) => $q->whereNull('societe_id'))
            ->when($classe, fn ($q) => $q->where('classe', $classe))
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('num_compte', 'like', "%{$search}%")
                ->orWhere('libelle', 'like', "%{$search}%")))
            ->orderBy('num_compte');

        $comptes = $query->limit(500)->get();
        $classes = PlanComptable::query()
            ->when($societeId, fn ($q) => $q->parSociete($societeId), fn ($q) => $q->whereNull('societe_id'))
            ->selectRaw('classe, count(*) as total')->groupBy('classe')->orderBy('classe')
            ->pluck('total', 'classe');

        return response()->json(['status' => 'success', 'comptes' => $comptes, 'classes' => $classes]);
    }

    public function planComptableSave(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'id' => 'nullable|exists:plan_comptable,id',
                'num_compte' => 'required|string|max:15',
                'libelle' => 'required|string|max:255',
                'classe' => 'required|integer|min:1|max:9',
                'type_compte_detail' => 'nullable|string|max:100',
                'est_compte_tiers' => 'boolean',
                'est_rapprochable' => 'boolean',
            ]);

            if (! empty($data['id'])) {
                $compte = PlanComptable::findOrFail($data['id']);
                if ($compte->est_systeme && ! $compte->societe_id) {
                    return response()->json(['errors' => ['Compte SYSCOHADA système non modifiable.']], 422);
                }
                $compte->update($this->planPayload($data, $societeId));
            } else {
                $compte = PlanComptable::create($this->planPayload($data, $societeId));
            }

            return response()->json(['status' => 'success', 'message' => 'Compte enregistré.', 'compte' => $compte->fresh()]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
    }

    public function planComptableImport(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        $societeId = SocieteContext::requireId();
        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['errors' => ['Le fichier est vide ou mal formaté.']], 422);
            }

            $header = array_map('strtoupper', array_map('trim', $rows[0]));
            $idxNumero = array_search('NUMERO', $header);
            $idxIntitule = array_search('INTITULE', $header);

            if ($idxNumero === false || $idxIntitule === false) {
                return response()->json(['errors' => ['Colonnes "NUMERO" et "INTITULE" obligatoires.']], 422);
            }

            $countCompte = 0;
            $countTiers = 0;

            DB::beginTransaction();

            foreach (array_slice($rows, 1) as $row) {
                $num = trim((string)($row[$idxNumero] ?? ''));
                $libelle = trim((string)($row[$idxIntitule] ?? ''));

                if ($num === '' || $libelle === '') continue;

                $classe = (int)substr($num, 0, 1);
                if ($classe < 1 || $classe > 9) continue;

                // On force le suivi tiers pour les comptes importés
                $payload = $this->planPayload([
                    'num_compte' => $num,
                    'libelle' => $libelle,
                    'classe' => $classe,
                    'est_compte_tiers' => true,
                    'est_rapprochable' => in_array($classe, [4, 5]),
                ], $societeId);

                // Create Or Update Compte
                $compte = PlanComptable::updateOrCreate(
                    ['societe_id' => $societeId, 'num_compte' => $num],
                    $payload
                );
                $countCompte++;

                // Création systématique du Tiers pour chaque compte importé
                $typeTiers = 'autre';
                if (str_starts_with($num, '411')) $typeTiers = 'client';
                elseif (str_starts_with($num, '401')) $typeTiers = 'fournisseur';
                elseif (str_starts_with($num, '42')) $typeTiers = 'salarie';
                elseif (str_starts_with($num, '43')) $typeTiers = 'organisme_social';
                elseif (str_starts_with($num, '44')) $typeTiers = 'administration';

                Tiers::updateOrCreate(
                    ['societe_id' => $societeId, 'code' => 'T-' . $num],
                    [
                        'nom' => $libelle,
                        'type' => $typeTiers,
                        'num_compte_collectif' => $num,
                        'actif' => true
                    ]
                );
                $countTiers++;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Importation réussie : $countCompte comptes et $countTiers fiches tiers synchronisés."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['Erreur : ' . $e->getMessage()]], 500);
        }
    }

    private function planPayload(array $data, int $societeId): array
    {
        $classe = (int) $data['classe'];

        return [
            'societe_id' => $societeId,
            'num_compte' => $data['num_compte'],
            'libelle' => $data['libelle'],
            'classe' => $classe,
            'num_compte_parent' => strlen($data['num_compte']) > 2 ? substr($data['num_compte'], 0, -2).'00' : null,
            'niveau' => 4,
            'type_compte' => in_array($classe, [6, 7, 8], true) ? 'gestion' : ($classe === 9 ? 'hors_bilan' : 'bilan'),
            'type_compte_detail' => $data['type_compte_detail'] ?? null,
            'sens_normal' => in_array($classe, [1, 4, 7], true) ? 'crediteur' : 'debiteur',
            'categorie_bilan' => 'non_applicable',
            'est_compte_detail' => true,
            'est_compte_tiers' => (bool) ($data['est_compte_tiers'] ?? false),
            'est_lettrable' => (bool) ($data['est_compte_tiers'] ?? false),
            'est_rapprochable' => (bool) ($data['est_rapprochable'] ?? false),
            'actif' => true,
            'est_systeme' => false,
        ];
    }

    public function journauxAll(): JsonResponse
    {
        $societeId = SocieteContext::requireId();

        return response()->json([
            'status' => 'success',
            'journaux' => Journal::where('societe_id', $societeId)->orderBy('ordre_affichage')->orderBy('code')->get(),
        ]);
    }

    public function journauxSave(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'id' => 'nullable|exists:journaux,id',
                'code' => 'required|string|max:10',
                'libelle' => 'required|string|max:150',
                'type' => 'required|in:achats,ventes,banque,caisse,operations_diverses,salaires,stocks,effets,immobilisations,ouverture,cloture,simulation',
                'compte_contrepartie' => 'nullable|string|max:15',
                'prefixe_piece' => 'nullable|string|max:10',
                'format_numerotation' => 'required|in:annuel,mensuel,continu',
                'padding_numero' => 'integer|min:1|max:8',
                'saisie_tiers_obligatoire' => 'boolean',
                'analytique_obligatoire' => 'boolean',
                'actif' => 'boolean',
                'ordre_affichage' => 'integer',
                'devise_defaut' => 'nullable|string|in:CDF,USD',
            ]);
            $payload = array_merge($data, ['societe_id' => $societeId]);
            $payload['devise_defaut'] = strtoupper($data['devise_defaut'] ?? Societe::find($societeId)?->devise_principale ?? 'CDF');
            unset($payload['id']);

            if (! empty($data['id'])) {
                $journal = Journal::where('societe_id', $societeId)->findOrFail($data['id']);
                $journal->update($payload);
            } else {
                $journal = Journal::create($payload);
            }

            return response()->json(['status' => 'success', 'message' => 'Journal enregistré.', 'journal' => $journal->fresh()]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
    }

    public function devisesAll(): JsonResponse
    {
        $societeId = SocieteContext::id();
        $societe = SocieteContext::societe();
        $devisePrincipale = strtoupper($societe?->devise_principale ?? 'CDF');

        return response()->json([
            'status' => 'success',
            'devise_principale' => $devisePrincipale,
            'devises' => Devise::actif()->orderBy('code_iso')->get(),
            'taux' => $societeId ? TauxChange::where('societe_id', $societeId)->orderByDesc('date_taux')->limit(100)->get() : [],
        ]);
    }

    public function tauxChangeSave(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'devise_code' => 'required|string|size:3',
                'date_taux' => 'required|date',
                'taux' => 'required|numeric|min:0',
                'taux_achat' => 'nullable|numeric|min:0',
                'taux_vente' => 'nullable|numeric|min:0',
            ]);
            $taux = TauxChange::updateOrCreate(
                ['societe_id' => $societeId, 'devise_code' => strtoupper($data['devise_code']), 'date_taux' => $data['date_taux']],
                ['taux' => $data['taux'], 'taux_achat' => $data['taux_achat'] ?? null, 'taux_vente' => $data['taux_vente'] ?? null, 'source' => 'manuel']
            );

            return response()->json(['status' => 'success', 'message' => 'Taux enregistré.', 'taux' => $taux]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
    }

    public function tiersAll(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $query = Tiers::where('societe_id', $societeId);
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('nom', 'like', "%{$search}%"));
        }

        return response()->json(['status' => 'success', 'tiers' => $query->orderBy('nom')->get()]);
    }

    public function tiersSave(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'id' => 'nullable|exists:tiers,id',
                'code' => 'required|string|max:30',
                'nom' => 'required|string|max:255',
                'type' => 'required|in:client,fournisseur,client_fournisseur,salarie,actionnaire,banque,organisme_social,administration,autre',
                'num_compte_collectif' => 'nullable|string|max:15',
                'email' => 'nullable|email',
                'telephone' => 'nullable|string|max:50',
                'ville' => 'nullable|string|max:100',
                'actif' => 'boolean',
            ]);
            $payload = array_merge($data, ['societe_id' => $societeId]);
            unset($payload['id']);

            if (! empty($data['id'])) {
                $tiers = Tiers::where('societe_id', $societeId)->findOrFail($data['id']);
                $tiers->update($payload);
            } else {
                $tiers = Tiers::create($payload);
            }

            return response()->json(['status' => 'success', 'message' => 'Tiers enregistré.', 'tiers' => $tiers->fresh()]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
    }

    public function societeDetail(): JsonResponse
    {
        $societeId = SocieteContext::id();
        if (! $societeId) {
            return response()->json(['status' => 'success', 'societe' => null, 'exercices' => [], 'devises' => Devise::actif()->orderBy('code_iso')->get(['code_iso', 'libelle'])]);
        }
        $societe = Societe::with(['exercices', 'banques'])->find($societeId);

        return response()->json([
            'status' => 'success',
            'societe' => array_merge($societe->toArray(), [
                'logo_url' => $societe->logo_url,
                'banques' => $societe->banques,
            ]),
            'exercices' => $societe->exercices,
            'devises' => Devise::actif()->orderBy('code_iso')->get(['code_iso', 'libelle', 'symbole']),
        ]);
    }

    public function societeLogo(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);
            $societe = Societe::findOrFail($societeId);
            // Supprimer l'ancien logo
            if ($societe->logo_path) {
                $relative = str_starts_with($societe->logo_path, 'http')
                    ? str_replace(url('/').'/', '', $societe->logo_path)
                    : $societe->logo_path;
                $oldFile = public_path($relative);
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $file = $request->file('logo');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            if (! is_dir(public_path('logos'))) {
                mkdir(public_path('logos'), 0755, true);
            }
            $file->move(public_path('logos'), $filename);
            $relativePath = 'logos/'.$filename;

            $societe->update(['logo_path' => $relativePath]);

            return response()->json([
                'status' => 'success',
                'message' => 'Logo enregistré.',
                'logo_url' => asset($relativePath),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->validator->errors()->all()
            ], 422);
        }
    }

    public function societeSave(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'nullable|exists:societes,id',
                'code' => 'required|string|max:20',
                'raison_sociale' => 'required|string|max:255',
                'forme_juridique' => 'nullable|string|max:50',
                'sigle' => 'nullable|string|max:50',
                'adresse' => 'nullable|string',
                'ville' => 'nullable|string|max:100',
                'pays' => 'nullable|string|max:100',
                'telephone' => 'nullable|string|max:50',
                'email' => 'nullable|email',
                'rccm' => 'nullable|string|max:100',
                'num_contribuable' => 'nullable|string|max:100',
                'identification_nationale' => 'nullable|string|max:100',
                'num_cnps' => 'nullable|string|max:100',
                'regime_fiscal' => 'nullable|string|max:50',
                'devise_principale' => 'required|string|size:3',
                'statut' => 'required|in:active,inactive,suspendue',
                'banques' => 'nullable|array',
                'banques.*.banque' => 'required_with:banques|string|max:150',
                'banques.*.numero_compte' => 'required_with:banques|string|max:80',
                'banques.*.devise' => 'nullable|string|size:3',
                'banques.*.est_defaut' => 'boolean',
            ]);

            $banques = $data['banques'] ?? [];
            unset($data['banques']);

            if (! empty($data['id'])) {
                $societe = Societe::findOrFail($data['id']);
                $societe->update($data);
            } else {
                $societe = Societe::create($data);
                SocieteContext::set($societe->id);
            }

            $this->syncBanques($societe->id, $banques);

            $societe = $societe->fresh(['banques']);

            return response()->json([
                'status' => 'success',
                'message' => 'Société enregistrée.',
                'societe' => array_merge($societe->toArray(), [
                    'logo_url' => $societe->logo_url,
                    'banques' => $societe->banques,
                ]),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
    }

    public function exerciceSave(Request $request): JsonResponse
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
            DB::transaction(function () use ($data, $societeId, &$exercice) {
                $payload = array_merge($data, ['societe_id' => $societeId]);
                unset($payload['id']);
                if (! empty($data['id'])) {
                    $exercice = Exercice::where('societe_id', $societeId)->findOrFail($data['id']);
                    $exercice->update($payload);
                } else {
                    $exercice = Exercice::create($payload);
                }
                if (! empty($data['est_courant'])) {
                    Exercice::where('societe_id', $societeId)->update(['est_courant' => false]);
                    $exercice->update(['est_courant' => true]);
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Exercice enregistré.', 'exercice' => $exercice->fresh()]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
    }

    public function exerciceCourant(Request $request): JsonResponse
    {
        $data = $request->validate(['exercice_id' => 'required|exists:exercices,id']);
        $exercice = Exercice::findOrFail($data['exercice_id']);
        SocieteContext::set($exercice->societe_id);
        DB::transaction(function () use ($exercice) {
            Exercice::where('societe_id', $exercice->societe_id)->update(['est_courant' => false]);
            $exercice->update(['est_courant' => true]);
        });

        return response()->json(['status' => 'success', 'message' => 'Exercice courant défini.', 'exercice' => $exercice->fresh()]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $banques
     */
    private function syncBanques(int $societeId, array $banques): void
    {
        SocieteBanque::where('societe_id', $societeId)->delete();
        $ordre = 0;
        foreach ($banques as $b) {
            $nom = trim((string) ($b['banque'] ?? ''));
            $compte = trim((string) ($b['numero_compte'] ?? ''));
            if ($nom === '' || $compte === '') {
                continue;
            }
            SocieteBanque::create([
                'societe_id' => $societeId,
                'banque' => $nom,
                'numero_compte' => $compte,
                'devise' => strtoupper($b['devise'] ?? 'CDF'),
                'est_defaut' => (bool) ($b['est_defaut'] ?? false),
                'ordre' => $ordre++,
            ]);
        }
    }
}
