<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use App\Models\Societe;
use App\Services\EtatsFinanciersService;
use App\Services\LivresComptablesService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EtatsController extends Controller
{
    public function __construct(
        protected EtatsFinanciersService $etats,
        protected LivresComptablesService $livres
    ) {}

    public function bilan(): View
    {
        return view('etats.bilan', ['page' => 'bilan', 'title' => 'Bilan (Actif / Passif / Capitaux propres)']);
    }

    public function compteResultat(): View
    {
        return view('etats.compte-resultat', ['page' => 'compte-resultat', 'title' => 'Compte de résultat']);
    }

    public function fluxTresorerie(): View
    {
        return view('etats.flux-tresorerie', ['page' => 'flux-tresorerie', 'title' => 'Tableau flux trésorerie']);
    }

    public function variationKp(): View
    {
        return view('etats.variation-kp', ['page' => 'variation-kp', 'title' => 'Tableau de variation KP']);
    }

    public function annexes(): View
    {
        return view('etats.annexes', ['page' => 'annexes', 'title' => 'Annexes SYSCOHADA']);
    }

    public function comparatif(): View
    {
        return view('etats.comparatif', ['page' => 'comparatif', 'title' => 'Comparatif N / N-1']);
    }

    public function exports(): View
    {
        return view('etats.exports', ['page' => 'exports', 'title' => 'Export PDF / Excel']);
    }

    public function metadata(): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);
        $options = $this->livres->optionsDefaut($societe);
        $exercices = Exercice::where('societe_id', $societeId)->orderByDesc('date_fin')->get(['id', 'libelle', 'date_debut', 'date_fin', 'est_courant']);
        $n1 = $exercice ? $this->etats->exercicePrecedent($societeId, $exercice) : null;

        return response()->json([
            'status' => 'success',
            'societe' => $societe,
            'exercice' => $exercice,
            'exercice_n1' => $n1,
            'exercices' => $exercices,
            'options' => $options,
            'date_arrete' => $exercice?->date_fin?->format('Y-m-d'),
            'date_debut' => $exercice?->date_debut?->format('Y-m-d'),
        ]);
    }

    protected function contexte(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);
        if ($request->filled('exercice_id')) {
            $exercice = Exercice::where('societe_id', $societeId)->findOrFail($request->integer('exercice_id'));
        }
        if (! $exercice) {
            return ['error' => 'Aucun exercice sélectionné.'];
        }

        $options = $this->livres->optionsDefaut($societe);
        $dateArrete = $request->get('date_arrete', $exercice->date_fin->format('Y-m-d'));
        $devise = strtoupper($request->get('devise_affichage', $options['devise_affichage']));
        $mode = $request->get('mode_conversion', $options['mode_conversion']);
        $avecN1 = $request->boolean('avec_n1', true);
        $n1 = $avecN1 ? $this->etats->exercicePrecedent($societeId, $exercice) : null;

        return compact('societe', 'exercice', 'dateArrete', 'devise', 'mode', 'n1');
    }

    public function apiBilan(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $ctx = $this->contexte($request, $societeId);
            if (isset($ctx['error'])) {
                return response()->json(['errors' => [$ctx['error']]], 422);
            }

            $data = $this->etats->bilan($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']);

            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\App\Exceptions\BilanDesequilibreException $e) {
            $payload = $e->context['payload'] ?? [
                'equilibre' => false,
                'ecart' => $e->ecart,
                'total_actif' => $e->totalActif,
                'total_passif' => $e->totalPassif,
                'total_capitaux_propres' => $e->totalCapitauxPropres,
                'actif' => [],
                'passif' => [],
            ];

            return response()->json([
                'status' => 'error',
                'errors' => [$e->getMessage()],
                'data' => array_merge($payload, [
                    'equilibre' => false,
                    'ecart' => $e->ecart,
                ]),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'errors' => ['Erreur lors du calcul du bilan : '.$e->getMessage()],
            ], 500);
        }
    }

    public function apiCompteResultat(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->contexte($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->etats->compteResultat($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function apiFluxTresorerie(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->contexte($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->etats->fluxTresorerie($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function apiVariationKp(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->contexte($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->etats->variationCapitauxPropres($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function apiAnnexes(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->etats->annexes()]);
    }

    public function apiComparatif(Request $request): JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->contexte($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = $this->etats->comparatif($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode']);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function exportCsv(Request $request, string $type): StreamedResponse|JsonResponse
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->contexte($request, $societeId);
        if (isset($ctx['error'])) {
            return response()->json(['errors' => [$ctx['error']]], 422);
        }

        $data = match ($type) {
            'bilan' => $this->etats->bilan($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            'compte-resultat' => $this->etats->compteResultat($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            'flux-tresorerie' => $this->etats->fluxTresorerie($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            'variation-kp' => $this->etats->variationCapitauxPropres($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            default => null,
        };

        if (! $data) {
            return response()->json(['errors' => ['Type d\'export inconnu.']], 422);
        }

        $filename = "etat_{$type}_{$ctx['dateArrete']}.csv";

        return response()->streamDownload(function () use ($type, $data) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            if ($type === 'bilan') {
                fputcsv($out, ['Bloc', 'Réf', 'Libellé', 'Compte', 'Montant N'], ';');
                foreach (['actif' => 'ACTIF', 'passif' => 'PASSIF'] as $key => $label) {
                    foreach ($data[$key] ?? [] as $l) {
                        fputcsv($out, [
                            $label,
                            $l['ref'] ?? '',
                            $l['libelle'] ?? '',
                            $l['num_compte'] ?? '',
                            $l['net_n'] ?? '',
                        ], ';');
                    }
                }
            } else {
                fputcsv($out, ['Réf', 'Libellé', 'Note', 'Montant N', 'Montant N-1'], ';');
                foreach ($data['lignes'] ?? [] as $l) {
                    fputcsv($out, [
                        $l['ref'] ?? '',
                        $l['libelle'] ?? '',
                        $l['note'] ?? '',
                        $l['montant_n'] ?? $l['cloture'] ?? '',
                        $l['montant_n1'] ?? $l['ouverture'] ?? '',
                    ], ';');
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
