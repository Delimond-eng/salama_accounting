<?php

namespace App\Http\Controllers;

use App\Models\DemandeFonds;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\Produit;
use App\Models\Tiers;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowEtape;
use App\Services\DemandeFondsService;
use App\Services\FacturationPdfService;
use App\Services\FacturationService;
use App\Services\PaiementFacturationService;
use App\Services\SaisieComptableService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class FacturationController extends Controller
{
    public function __construct(
        protected FacturationService $facturation,
        protected PaiementFacturationService $paiements,
        protected DemandeFondsService $demandes,
        protected FacturationPdfService $pdf,
        protected SaisieComptableService $saisie
    ) {}

    public function index(): View
    {
        return view('facturation.index', ['page' => 'index', 'title' => 'Facturation']);
    }

    public function facturesClients(): View
    {
        return view('facturation.factures-liste', [
            'page' => 'clients',
            'type' => Facture::TYPE_VENTE_CLIENT,
            'title' => 'Factures clients',
        ]);
    }

    public function facturesFournisseurs(): View
    {
        return view('facturation.factures-liste', [
            'page' => 'fournisseurs',
            'type' => Facture::TYPE_ACHAT_FOURNISSEUR,
            'title' => 'Factures fournisseurs',
        ]);
    }

    public function factureForm(?string $type = 'clients', ?int $id = null): View
    {
        $typeDoc = $type === 'fournisseurs'
            ? Facture::TYPE_ACHAT_FOURNISSEUR
            : Facture::TYPE_VENTE_CLIENT;

        return view('facturation.facture-form', [
            'page' => $type,
            'type_document' => $typeDoc,
            'facture_id' => $id,
            'title' => $id ? 'Modifier facture' : 'Nouvelle facture',
        ]);
    }

    public function avoirsClients(): View
    {
        return view('facturation.factures-liste', [
            'page' => 'avoirs-clients',
            'type' => Facture::TYPE_AVOIR_CLIENT,
            'title' => 'Avoirs clients',
        ]);
    }

    public function avoirsFournisseurs(): View
    {
        return view('facturation.factures-liste', [
            'page' => 'avoirs-fournisseurs',
            'type' => Facture::TYPE_AVOIR_FOURNISSEUR,
            'title' => 'Avoirs fournisseurs',
        ]);
    }

    public function produits(): View
    {
        return view('facturation.produits', ['page' => 'produits', 'title' => 'Produits & services']);
    }

    public function paiements(): View
    {
        return view('facturation.paiements', ['page' => 'paiements', 'title' => 'Paiements']);
    }

    public function echeancierClients(): View
    {
        return view('facturation.echeancier', ['page' => 'echeancier-clients', 'cible' => 'clients', 'title' => 'Échéancier clients']);
    }

    public function echeancierFournisseurs(): View
    {
        return view('facturation.echeancier', ['page' => 'echeancier-fournisseurs', 'cible' => 'fournisseurs', 'title' => 'Échéancier fournisseurs']);
    }

    public function demandesFonds(): View
    {
        return view('facturation.demandes-liste', ['page' => 'demandes', 'title' => 'Demandes de fonds']);
    }

    public function demandeForm(?int $id = null): View
    {
        return view('facturation.demande-form', [
            'page' => 'demande',
            'demande_id' => $id,
            'title' => $id ? 'Demande de fonds' : 'Nouvelle demande',
        ]);
    }

    public function workflow(): View
    {
        return view('facturation.workflow', ['page' => 'workflow', 'title' => 'Workflow demandes de fonds']);
    }

    public function metadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $exercice = $this->saisie->exerciceCourant($societeId);

        return response()->json([
            'status' => 'success',
            'exercice' => $exercice,
            'taux_tva_defaut' => config('facturation.taux_tva_defaut'),
            'comptes' => config('facturation.comptes'),
            'types_document' => [
                Facture::TYPE_VENTE_CLIENT,
                Facture::TYPE_ACHAT_FOURNISSEUR,
                Facture::TYPE_AVOIR_CLIENT,
                Facture::TYPE_AVOIR_FOURNISSEUR,
            ],
        ]);
    }

    public function apiFactures(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $q = Facture::parSociete($societeId)->with('tiers:id,code,nom')->orderByDesc('date_facture');

        if ($type = $request->get('type_document')) {
            $q->where('type_document', $type);
        }
        if ($statut = $request->get('statut')) {
            $q->where('statut', $statut);
        }
        if ($search = trim((string) $request->get('search', ''))) {
            $q->where(fn ($s) => $s->where('numero', 'like', "%{$search}%")
                ->orWhereHas('tiers', fn ($t) => $t->where('nom', 'like', "%{$search}%")));
        }

        return response()->json(['status' => 'success', 'factures' => $q->limit(500)->get()]);
    }

    public function apiFactureShow(int $id): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $facture = Facture::parSociete($societeId)->with(['lignes.produit', 'tiers', 'factureOrigine', 'paiements'])->findOrFail($id);

        return response()->json(['status' => 'success', 'facture' => $facture]);
    }

    public function apiFactureSave(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'id' => 'nullable|integer',
                'type_document' => 'required|string',
                'tiers_id' => 'required|integer',
                'date_facture' => 'required|date',
                'date_echeance' => 'nullable|date',
                'objet' => 'nullable|string',
                'tva_active' => 'boolean',
                'taux_tva' => 'nullable|numeric',
                'devise' => 'nullable|string|size:3',
                'facture_origine_id' => 'nullable|integer',
                'notes' => 'nullable|string',
                'lignes' => 'required|array|min:1',
                'lignes.*.libelle' => 'required|string',
                'lignes.*.quantite' => 'nullable|numeric',
                'lignes.*.prix_unitaire' => 'nullable|numeric',
                'lignes.*.compte_comptable' => 'nullable|string',
                'lignes.*.produit_id' => 'nullable|integer',
            ]);

            $exercice = $this->saisie->exerciceCourant($societeId);
            $entete = collect($data)->except(['lignes', 'id'])->merge([
                'exercice_id' => $exercice?->id,
                'devise' => strtoupper($data['devise'] ?? 'CDF'),
            ])->all();

            $facture = $this->facturation->enregistrer($societeId, $entete, $data['lignes'], $data['id'] ?? null);

            return response()->json(['status' => 'success', 'facture' => $facture, 'message' => 'Facture enregistrée.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function apiFactureValider(int $id): JsonResponse
    {
        try {
            $facture = $this->facturation->valider(SocieteContext::requireId(), $id);

            return response()->json(['status' => 'success', 'facture' => $facture, 'message' => 'Facture validée et écriture générée.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function apiFactureAnnuler(Request $request, int $id): JsonResponse
    {
        try {
            $facture = $this->facturation->annuler(SocieteContext::requireId(), $id, $request->get('motif'));

            return response()->json(['status' => 'success', 'facture' => $facture]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function pdfFacture(int $id)
    {
        $facture = Facture::parSociete(SocieteContext::requireId())->findOrFail($id);

        return $this->pdf->facture($facture)->download("facture_{$facture->numero}.pdf");
    }

    public function apiProduits(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $q = Produit::parSociete($societeId)->where('actif', true)->orderBy('libelle');
        if ($search = trim((string) request('search', ''))) {
            $q->where(fn ($s) => $s->where('libelle', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        return response()->json(['status' => 'success', 'produits' => $q->get()]);
    }

    public function apiProduitSave(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'id' => 'nullable|integer',
            'code' => 'nullable|string|max:40',
            'libelle' => 'required|string',
            'prix_unitaire' => 'nullable|numeric',
            'compte_vente' => 'nullable|string',
            'compte_achat' => 'nullable|string',
        ]);

        $produit = isset($data['id'])
            ? Produit::parSociete($societeId)->findOrFail($data['id'])
            : new Produit(['societe_id' => $societeId]);

        $produit->fill($data);
        $produit->save();

        return response()->json(['status' => 'success', 'produit' => $produit]);
    }

    public function apiTiers(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $q = Tiers::where('societe_id', $societeId)->orderBy('nom');

        if ($cible = $request->get('cible')) {
            if ($cible === 'client') {
                $q->whereIn('type', ['client', 'client_fournisseur']);
            } elseif ($cible === 'fournisseur') {
                $q->whereIn('type', ['fournisseur', 'client_fournisseur']);
            }
        }

        return response()->json(['status' => 'success', 'tiers' => $q->get(['id', 'code', 'nom', 'type', 'num_compte_collectif'])]);
    }

    public function apiPayerFacture(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'montant' => 'nullable|numeric',
                'methode' => 'required|in:banque,caisse',
                'compte_tresorerie' => 'nullable|string',
                'date_paiement' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            $paiement = $this->paiements->payerFacture(SocieteContext::requireId(), $id, $data);

            return response()->json(['status' => 'success', 'paiement' => $paiement, 'message' => 'Paiement enregistré.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function pdfRecu(int $id)
    {
        $paiement = Paiement::parSociete(SocieteContext::requireId())->findOrFail($id);

        return $this->pdf->recuPaiement($paiement)->download("recu_{$paiement->numero}.pdf");
    }

    public function apiPaiements(): JsonResponse
    {
        $paiements = Paiement::parSociete(SocieteContext::requireId())
            ->with(['facture:id,numero', 'user:id,name'])
            ->orderByDesc('date_paiement')
            ->limit(200)
            ->get();

        return response()->json(['status' => 'success', 'paiements' => $paiements]);
    }

    public function apiEcheancier(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $cible = $request->get('cible', 'clients');
        $dateRef = $request->get('date_ref');

        $items = $cible === 'fournisseurs'
            ? $this->facturation->echeancierFournisseurs($societeId, $dateRef)
            : $this->facturation->echeancierClients($societeId, $dateRef);

        return response()->json(['status' => 'success', 'items' => $items]);
    }

    public function apiDemandes(): JsonResponse
    {
        $demandes = DemandeFonds::parSociete(SocieteContext::requireId())
            ->with(['demandeur:id,name', 'etapeCourante', 'workflow'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json(['status' => 'success', 'demandes' => $demandes]);
    }

    public function apiDemandeShow(int $id): JsonResponse
    {
        $demande = DemandeFonds::parSociete(SocieteContext::requireId())
            ->with(['demandeur', 'etapeCourante', 'workflow.etapes', 'validations.user', 'historiques.user', 'paiements'])
            ->findOrFail($id);

        return response()->json(['status' => 'success', 'demande' => $demande]);
    }

    public function apiDemandeSave(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'devise' => 'nullable|string|size:3',
                'motif' => 'required|string',
                'journal_id' => 'nullable|integer',
                'workflow_definition_id' => 'nullable|integer',
            ]);

            $demande = $this->demandes->creer(SocieteContext::requireId(), $data);

            return response()->json(['status' => 'success', 'demande' => $demande, 'message' => 'Demande soumise.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function apiDemandeTraiter(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'decision' => 'required|in:approuve,rejete',
                'commentaire' => 'nullable|string',
                'compte_debit' => 'nullable|string',
                'compte_credit' => 'nullable|string',
                'methode' => 'nullable|in:banque,caisse',
                'compte_tresorerie' => 'nullable|string',
                'executer_paiement' => 'boolean',
            ]);

            $demande = $this->demandes->traiterEtape(
                SocieteContext::requireId(),
                $id,
                $data['decision'],
                $data
            );

            return response()->json(['status' => 'success', 'demande' => $demande]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function apiWorkflows(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $workflows = WorkflowDefinition::parSociete($societeId)
            ->with('etapes')
            ->where('type_workflow', 'demande_fonds')
            ->get();

        return response()->json(['status' => 'success', 'workflows' => $workflows]);
    }

    public function apiWorkflowSave(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'id' => 'nullable|integer',
            'code' => 'required|string|max:40',
            'libelle' => 'required|string',
            'est_defaut' => 'boolean',
            'etapes' => 'required|array|min:1',
            'etapes.*.ordre' => 'required|integer',
            'etapes.*.code' => 'required|string',
            'etapes.*.libelle' => 'required|string',
            'etapes.*.type_etape' => 'required|string',
            'etapes.*.role_requis' => 'nullable|string',
            'etapes.*.imputation_comptable' => 'boolean',
            'etapes.*.execution_paiement' => 'boolean',
        ]);

        if (! empty($data['est_defaut'])) {
            WorkflowDefinition::parSociete($societeId)->update(['est_defaut' => false]);
        }

        $wf = isset($data['id'])
            ? WorkflowDefinition::parSociete($societeId)->findOrFail($data['id'])
            : new WorkflowDefinition(['societe_id' => $societeId, 'type_workflow' => 'demande_fonds']);

        $wf->fill([
            'code' => $data['code'],
            'libelle' => $data['libelle'],
            'est_defaut' => $data['est_defaut'] ?? false,
            'actif' => true,
        ]);
        $wf->save();

        $wf->etapes()->delete();
        foreach ($data['etapes'] as $e) {
            WorkflowEtape::create([
                'workflow_definition_id' => $wf->id,
                'ordre' => $e['ordre'],
                'code' => $e['code'],
                'libelle' => $e['libelle'],
                'type_etape' => $e['type_etape'],
                'role_requis' => $e['role_requis'] ?? null,
                'imputation_comptable' => $e['imputation_comptable'] ?? false,
                'execution_paiement' => $e['execution_paiement'] ?? false,
                'actif' => true,
            ]);
        }

        return response()->json(['status' => 'success', 'workflow' => $wf->load('etapes')]);
    }
}
