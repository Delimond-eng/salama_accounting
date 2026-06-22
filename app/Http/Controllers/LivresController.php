<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Societe;
use App\Services\DeviseConversionService;
use App\Services\LivresComptablesService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LivresController extends Controller
{
    public function __construct(
        protected LivresComptablesService $livres,
        protected DeviseConversionService $devises
    ) {}

    public function journalGeneral(): View
    {
        return view('livres.journal', ['page' => 'journal', 'title' => 'Journal général']);
    }

    public function grandLivre(): View
    {
        return view('livres.grand-livre', ['page' => 'grand-livre', 'title' => 'Grand livre']);
    }

    public function balanceGenerale(): View
    {
        return view('livres.balance', ['page' => 'balance', 'title' => 'Balance générale']);
    }

    public function balanceAuxiliaire(): View
    {
        return view('livres.auxiliaire', ['page' => 'auxiliaire', 'title' => 'Balance auxiliaire']);
    }

    public function lettrage(): View
    {
        return view('livres.lettrage', ['page' => 'lettrage', 'title' => 'Lettrage des comptes']);
    }

    public function comptesTiers(): View
    {
        return view('livres.comptes-tiers', ['page' => 'comptes-tiers', 'title' => 'Comptes de tiers']);
    }

    public function livreBanque(): View
    {
        return view('livres.tresorerie', [
            'page' => 'banque',
            'type' => 'banque',
            'title' => 'Livre de banque',
        ]);
    }

    public function livreCaisse(): View
    {
        return view('livres.tresorerie', [
            'page' => 'caisse',
            'type' => 'caisse',
            'title' => 'Livre de caisse',
        ]);
    }

    public function metadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);
        $options = $this->livres->optionsDefaut($societe);

        $devisePrincipale = $societe->devise_principale ?? 'CDF';
        $this->devises->setDevisePrincipale($devisePrincipale);
        $today = now()->toDateString();

        return response()->json([
            'status' => 'success',
            'societe' => $societe,
            'exercice' => $exercice,
            'options' => $options,
            'journaux' => Journal::where('societe_id', $societeId)->orderBy('code')->get(['id', 'code', 'libelle']),
            'taux_usd' => $this->devises->tauxJournalier($societeId, 'USD', $today),
            'date_taux' => $today,
            'date_debut' => $exercice?->date_debut?->format('Y-m-d'),
            'date_fin' => $exercice?->date_fin?->format('Y-m-d'),
        ]);
    }

    public function savePreferences(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $data = $request->validate([
            'mode_devise' => 'nullable|string|in:'.implode(',', \App\Support\DeviseMode::ids()),
            'devise_affichage' => 'nullable|string|size:3',
            'mode_conversion' => 'nullable|in:origine,actuel',
            'scope_devise' => 'nullable|string',
        ]);

        $societe = Societe::findOrFail($societeId);
        $params = $societe->parametres ?? [];

        // Le mode unifié est prioritaire ; on en dérive les paramètres internes stockés.
        $resolved = $this->livres->resoudreFiltresDevise($societe, $data);
        $params['mode_devise'] = $resolved['mode_devise'];
        $params['devise_affichage'] = $resolved['devise_affichage'];
        $params['scope_devise'] = $resolved['scope_devise'];
        $params['mode_conversion'] = $resolved['mode_conversion'];
        $societe->update(['parametres' => $params]);

        return response()->json(['status' => 'success', 'message' => 'Préférences devise enregistrées.', 'options' => $this->livres->optionsDefaut($societe->fresh())]);
    }

    protected function filtres(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);

        $options = $this->livres->resoudreFiltresDevise($societe, [
            'mode_devise' => $request->get('mode_devise'),
            'devise_affichage' => $request->get('devise_affichage'),
            'scope_devise' => $request->get('scope_devise'),
            'mode_conversion' => $request->get('mode_conversion'),
        ]);

        $dateDebut = $request->get('date_debut', $exercice?->date_debut?->format('Y-m-d'));
        $dateFin = $request->get('date_fin', $exercice?->date_fin?->format('Y-m-d'));
        $deviseAffichage = $options['devise_affichage'];
        $modeConversion = $options['mode_conversion'];
        $scopeDevise = $options['scope_devise'];
        $modeDevise = $options['mode_devise'];

        return compact('societe', 'exercice', 'dateDebut', 'dateFin', 'deviseAffichage', 'modeConversion', 'scopeDevise', 'modeDevise', 'options');
    }

    public function apiBalance(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        if (! $f['exercice']) {
            return response()->json(['errors' => ['Aucun exercice courant.']], 422);
        }

        $data = $this->livres->balanceGenerale(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $request->integer('classe') ?: null,
            $f['scopeDevise']
        );

        return response()->json(['status' => 'success', 'data' => $data, 'filtres' => $f]);
    }

    public function apiJournal(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        if (! $f['exercice']) {
            return response()->json(['errors' => ['Aucun exercice courant.']], 422);
        }

        $lignes = $this->livres->journalGeneral(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $request->integer('journal_id') ?: null,
            $f['scopeDevise']
        );

        return response()->json(['status' => 'success', 'lignes' => $lignes, 'filtres' => $f]);
    }

    public function apiGrandLivre(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        $numCompte = $request->validate(['num_compte' => 'required|string'])['num_compte'];

        if (! $f['exercice']) {
            return response()->json(['errors' => ['Aucun exercice courant.']], 422);
        }

        $data = $this->livres->grandLivre(
            $societeId,
            $f['exercice']->id,
            $numCompte,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $f['scopeDevise']
        );

        return response()->json(['status' => 'success', 'data' => $data, 'filtres' => $f]);
    }

    public function apiGrandLivreGeneral(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        if (! $f['exercice']) {
            return response()->json(['errors' => ['Aucun exercice courant.']], 422);
        }

        $data = $this->livres->grandLivreGeneral(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $f['scopeDevise']
        );

        return response()->json(['status' => 'success', 'data' => $data, 'filtres' => $f]);
    }

    public function apiAuxiliaire(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        if (! $f['exercice']) {
            return response()->json(['errors' => ['Aucun exercice courant.']], 422);
        }

        $lignes = $this->livres->balanceAuxiliaire(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $request->get('type_tiers'),
            $f['scopeDevise']
        );

        return response()->json(['status' => 'success', 'lignes' => $lignes, 'filtres' => $f]);
    }

    public function apiLettrage(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $numCompte = $request->get('num_compte', '41');
        $tiersId = $request->integer('tiers_id') ?: null;

        $lignes = $this->livres->lettrageNonLettre($societeId, $numCompte, $tiersId);

        return response()->json(['status' => 'success', 'lignes' => $lignes]);
    }

    public function apiComptesTiers(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        if (! $f['exercice']) {
            return response()->json(['errors' => ['Aucun exercice courant.']], 422);
        }

        $tiers = $this->livres->comptesTiers($societeId);
        $soldes = $this->livres->balanceAuxiliaire(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            null,
            $f['scopeDevise']
        )->keyBy('tiers_id');

        $result = $tiers->map(function ($t) use ($soldes) {
            $s = $soldes->get($t->id);

            return array_merge($t->toArray(), [
                'solde_fin_debiteur' => $s['solde_fin_debiteur'] ?? 0,
                'solde_fin_crediteur' => $s['solde_fin_crediteur'] ?? 0,
            ]);
        });

        return response()->json(['status' => 'success', 'tiers' => $result, 'filtres' => $f]);
    }

    public function apiComptesTresorerie(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $type = $request->validate(['type' => 'required|in:banque,caisse'])['type'];
        $comptes = $this->livres->comptesTresorerie($societeId, $type);

        return response()->json(['status' => 'success', 'comptes' => $comptes]);
    }

    public function apiLivreTresorerie(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $f = $this->filtres($request, $societeId);
        $validated = $request->validate([
            'num_compte' => 'required|string',
            'type' => 'required|in:banque,caisse',
        ]);

        if (! $f['exercice']) {
            return response()->json(['errors' => ['Aucun exercice courant.']], 422);
        }

        $data = $this->livres->livreTresorerie(
            $societeId,
            $f['exercice']->id,
            $validated['num_compte'],
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $validated['type'],
            $f['scopeDevise']
        );

        $today = now()->toDateString();
        $dateRef = min($today, $f['exercice']->date_fin->format('Y-m-d'));

        $synthese = $this->livres->syntheseTresorerie(
            $societeId,
            $f['exercice']->id,
            $validated['type'],
            $dateRef,
            $f['deviseAffichage'],
            $f['modeConversion'],
            $f['scopeDevise']
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'synthese' => $synthese,
            'filtres' => $f,
        ]);
    }
}
