<?php

namespace App\Http\Controllers;

use App\Models\BonCommande;
use App\Models\BonLivraison;
use App\Models\Entrepot;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\Tiers;
use App\Services\StockPdfService;
use App\Services\StockService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class StockController extends Controller
{
    public function __construct(
        protected StockService $stock,
        protected StockPdfService $pdf
    ) {}

    public function inventaire(): View
    {
        return view('facturation.stock-inventaire', ['page' => 'stock', 'title' => 'Inventaire & stock']);
    }

    public function bonsCommande(): View
    {
        return view('facturation.stock-bons-commande', ['page' => 'bons-commande', 'title' => 'Bons de commande']);
    }

    public function mouvements(): View
    {
        return view('facturation.stock-mouvements', ['page' => 'mouvements', 'title' => 'Mouvements de stock']);
    }

    public function apiInventaire(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $produits = Produit::parSociete($societeId)
            ->where('actif', true)
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle', 'unite', 'type_article', 'gestion_stock', 'stock_actuel', 'stock_minimum', 'prix_unitaire_cdf', 'prix_unitaire_usd']);

        return response()->json(['status' => 'success', 'produits' => $produits]);
    }

    public function apiMouvements(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $mouvements = MouvementStock::parSociete($societeId)
            ->with(['produit:id,code,libelle', 'user:id,name'])
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        return response()->json(['status' => 'success', 'mouvements' => $mouvements]);
    }

    public function apiBonsCommande(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $bons = BonCommande::parSociete($societeId)
            ->with('tiers:id,code,nom')
            ->orderByDesc('date_commande')
            ->limit(200)
            ->get();

        return response()->json(['status' => 'success', 'bons' => $bons]);
    }

    public function apiBonCommandeSave(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'tiers_id' => 'required|integer',
                'devise' => 'required|in:CDF,USD',
                'date_commande' => 'nullable|date',
                'date_livraison_prevue' => 'nullable|date',
                'notes' => 'nullable|string',
                'lignes' => 'required|array|min:1',
                'lignes.*.libelle' => 'required|string',
                'lignes.*.quantite' => 'nullable|numeric',
                'lignes.*.prix_unitaire' => 'nullable|numeric',
                'lignes.*.produit_id' => 'nullable|integer',
            ]);

            $bc = $this->stock->enregistrerBonCommande($societeId, $data, $data['lignes']);

            return response()->json(['status' => 'success', 'bon' => $bc, 'message' => 'Bon de commande enregistré.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function apiMouvementManuel(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $data = $request->validate([
                'produit_id' => 'required|integer',
                'type_mouvement' => 'required|in:entree,sortie,ajustement,inventaire',
                'quantite' => 'required|numeric|min:0.0001',
                'libelle' => 'required|string',
                'date_mouvement' => 'nullable|date',
            ]);

            $m = $this->stock->mouvement(
                $societeId,
                (int) $data['produit_id'],
                $data['type_mouvement'],
                (float) $data['quantite'],
                $data['libelle'],
                $data['date_mouvement'] ?? now()->toDateString()
            )->load(['produit', 'user']);

            return response()->json([
                'status' => 'success',
                'mouvement' => $m,
                'message' => 'Mouvement enregistré.',
                'pdf_url' => route('accounting.facturation.stock.mouvement.pdf', ['id' => $m->id]),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    public function pdfMouvement(int $id)
    {
        $mouvement = MouvementStock::parSociete(SocieteContext::requireId())->findOrFail($id);

        return $this->pdf->bon($mouvement)->download('bon_stock_'.($mouvement->numero ?? $mouvement->id).'.pdf');
    }

    public function apiMetadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();

        return response()->json([
            'status' => 'success',
            'fournisseurs' => Tiers::where('societe_id', $societeId)
                ->whereIn('type', ['fournisseur', 'client_fournisseur'])
                ->orderBy('nom')
                ->get(['id', 'code', 'nom']),
            'produits' => Produit::parSociete($societeId)->where('actif', true)->where('gestion_stock', true)->orderBy('libelle')->get(),
            'devises' => config('facturation.devises_autorisees', ['CDF', 'USD']),
        ]);
    }
}
