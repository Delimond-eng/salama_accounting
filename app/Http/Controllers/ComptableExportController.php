<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ecriture;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\User;
use App\Models\PlanComptable;
use App\Models\Societe;
use App\Models\Tiers;
use App\Models\DeclarationFiscale;
use App\Services\AnalytiqueComptableService;
use App\Services\ComptableExportService;
use App\Services\EtatsFinanciersService;
use App\Services\FiscaliteService;
use App\Services\LivresComptablesService;
use App\Services\SaisieComptableService;
use App\Support\SocieteContext;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ComptableExportController extends Controller
{
    public function __construct(
        protected ComptableExportService $export,
        protected LivresComptablesService $livres,
        protected EtatsFinanciersService $etats,
        protected FiscaliteService $fiscalite,
        protected SaisieComptableService $saisie,
        protected AnalytiqueComptableService $analytique
    ) {}

    public function livres(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();
        $f = $this->livresFiltres($request, $societeId);
        if (!$f['exercice']) abort(422, 'Aucun exercice courant.');

        $title = config("accounting_exports.livres.{$type}.title", "Livre comptable");
        $exportType = $type;
        if (in_array($type, ['banque', 'caisse'])) {
            $exportType = 'tresorerie';
            $title = ($type === 'banque' ? 'Livre de banque' : 'Livre de caisse');
        }

        $meta = $this->metaFiltres($f);
        [$headers, $rows] = match ($exportType) {
            'balance' => $this->rowsBalance($societeId, $f, $request),
            'journal' => $this->rowsJournal($societeId, $f, $request),
            'grand-livre' => $this->rowsGrandLivre($societeId, $f, $request),
            'auxiliaire' => $this->rowsAuxiliaire($societeId, $f, $request),
            'comptes-tiers' => $this->rowsComptesTiers($societeId, $f),
            'tresorerie' => $this->rowsTresorerie($societeId, $f, $request, $type),
            'lettrage' => $this->rowsLettrage($societeId, $request),
            default => abort(404),
        };

        return $this->export->respond($format, $headers, $rows, "livre_{$type}", $title, $meta, $f['societe']);
    }

    public function etats(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->etatsCtx($request, $societeId);
        if (isset($ctx['error'])) abort(422, $ctx['error']);

        if ($type === 'globaux') {
            return $this->exportGlobaux($ctx, $format);
        }

        $exN1 = $ctx['exerciceN1'] instanceof Exercice ? $ctx['exerciceN1'] : null;
        $data = match ($type) {
            'bilan' => $this->etats->bilan($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'compte-resultat' => $this->etats->compteResultat($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'flux-tresorerie' => $this->etats->fluxTresorerie($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'variation-kp' => $this->etats->variationCapitauxPropres($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'comparatif' => $this->etats->comparatif($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode']),
            default => abort(404),
        };

        [$headers, $rows] = $this->formatEtatsRows($type, $data);

        $meta = ['Date arrêté' => $ctx['dateArrete'], 'Devise' => $ctx['devise']];
        if ($type === 'bilan') {
            $meta['Total Actif'] = $this->export->formatNum($data['total_actif']);
            $meta['Total Passif'] = $this->export->formatNum($data['total_passif']);
            $meta['Résultat Net'] = $this->export->formatNum($data['resultat_exercice']);
        }

        return $this->export->respond($format, $headers, $rows, 'etat_'.$type, 'État — '.$type, $meta, $ctx['societe']);
    }

    public function analytique(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);

        $paramsDevise = [
            'devise_affichage' => $request->get('devise_affichage'),
            'mode_conversion' => $request->get('mode_conversion'),
            'scope_devise' => $request->get('scope_devise'),
        ];

        $f = [
            'date_debut' => $request->get('date_debut', $exercice?->date_debut?->format('Y-m-d')),
            'date_fin' => $request->get('date_fin', $exercice?->date_fin?->format('Y-m-d')),
            'axe_id' => $request->integer('axe_id') ?: null,
            'section_id' => $request->integer('section_id') ?: null,
            'journal_id' => $request->integer('journal_id') ?: null,
        ];

        if (!$exercice) abort(422, 'Aucun exercice courant.');

        [$headers, $rows, $title] = match ($type) {
            'balance' => $this->rowsAnalytiqueBalance($societeId, $exercice->id, $f, $paramsDevise),
            'grand-livre' => $this->rowsAnalytiqueGrandLivre($societeId, $exercice->id, $f, $paramsDevise),
            'rentabilite' => $this->rowsAnalytiqueRentabilite($societeId, $exercice->id, $f, $paramsDevise),
            'centres-cout' => $this->rowsAnalytiqueCentresCout($societeId, $exercice->id, $f, $paramsDevise),
            default => abort(404),
        };

        $meta = [
            'Période' => $f['date_debut'] . ' au ' . $f['date_fin'],
            'Exercice' => $exercice->libelle,
        ];

        return $this->export->respond($format, $headers, $rows, "analytique_{$type}", $title, $meta, $societe);
    }

    protected function rowsAnalytiqueBalance(int $societeId, int $exerciceId, array $f, array $params): array
    {
        $data = $this->analytique->balanceAnalytique($societeId, $exerciceId, $f['date_debut'], $f['date_fin'], $f['axe_id'], $f['section_id'], $f['journal_id'], $params);
        $headers = ['Axe', 'Code', 'Libellé', 'Débit', 'Crédit', 'Solde'];
        $rows = [];
        foreach ($data['items'] as $item) {
            $rows[] = [
                $item['axe_libelle'], $item['section_code'], $item['section_libelle'],
                $this->export->formatNum($item['debit']), $this->export->formatNum($item['credit']), $this->export->formatNum($item['solde'])
            ];
        }
        $rows[] = ['=== TOTAL GÉNÉRAL', '', '', $this->export->formatNum($data['totaux']['debit']), $this->export->formatNum($data['totaux']['credit']), $this->export->formatNum($data['totaux']['debit'] - $data['totaux']['credit'])];
        return [$headers, $rows, 'Balance Analytique'];
    }

    protected function rowsAnalytiqueGrandLivre(int $societeId, int $exerciceId, array $f, array $params): array
    {
        $data = $this->analytique->grandLivreAnalytique($societeId, $exerciceId, $f['date_debut'], $f['date_fin'], $f['section_id'], $f['journal_id'], $params);
        $headers = ['Date', 'Pièce', 'Journal', 'Compte', 'Axe', 'Section', 'Libellé', 'Débit', 'Crédit'];
        $rows = [];
        foreach ($data['lignes'] as $l) {
            $rows[] = [
                $l->date_ecriture, $l->num_piece, $l->journal_code, $l->num_compte,
                $l->axe_code, $l->section_libelle, $l->libelle_ligne ?: $l->libelle_ecriture,
                $this->export->formatNum($l->debit), $this->export->formatNum($l->credit)
            ];
        }
        return [$headers, $rows, 'Grand Livre Analytique'];
    }

    protected function rowsAnalytiqueRentabilite(int $societeId, int $exerciceId, array $f, array $params): array
    {
        $data = $this->analytique->rentabiliteProjets($societeId, $exerciceId, $f['date_debut'], $f['date_fin'], $f['axe_id'], $params);
        $headers = ['Axe', 'Projet / Section', 'Charges', 'Produits', 'Résultat Net', 'Marge %'];
        $rows = [];
        foreach ($data['projets'] as $p) {
            $marge = $p['produits'] > 0 ? ($p['resultat'] / $p['produits'] * 100) : 0;
            $rows[] = [
                $p['axe_libelle'], $p['libelle'],
                $this->export->formatNum($p['charges']), $this->export->formatNum($p['produits']),
                $this->export->formatNum($p['resultat']), number_format($marge, 1) . ' %'
            ];
        }
        return [$headers, $rows, 'Rentabilité Analytique'];
    }

    protected function rowsAnalytiqueCentresCout(int $societeId, int $exerciceId, array $f, array $params): array
    {
        $data = $this->analytique->depensesParAxe($societeId, $exerciceId, $f['date_debut'], $f['date_fin'], $f['axe_id'], $params);
        $headers = ['Axe', 'Section', 'Dépenses / Charges'];
        $rows = [];
        foreach ($data['items'] as $item) {
            $rows[] = [
                $item['axe_libelle'], $item['section_libelle'],
                $this->export->formatNum($item['depenses'])
            ];
        }
        return [$headers, $rows, 'Analyse des Centres de Coût'];
    }

    protected function formatEtatsRows(string $type, array $data): array
    {
        if ($type === 'bilan' || ($type === 'comparatif' && isset($data['bilan']))) {
            $bilanData = ($type === 'comparatif') ? $data['bilan'] : $data;
            $headers = ['Bloc', 'Réf', 'Libellé', 'Compte', 'Montant N', 'Montant N-1'];
            $rows = [];
            foreach (['actif' => 'ACTIF', 'passif' => 'PASSIF'] as $key => $label) {
                $rows[] = ['### ' . $label, '', '', '', '', ''];
                foreach ($bilanData[$key] ?? [] as $l) {
                    $lib = $l['libelle'] ?? '';
                    if (($l['type'] ?? '') === 'titre') $lib = '### ' . $lib;
                    if (($l['type'] ?? '') === 'total') $lib = '=== ' . $lib;
                    $rows[] = [$label, $l['ref'] ?? '', $lib, $l['num_compte'] ?? '', $this->export->formatNum($l['net_n'] ?? 0), $this->export->formatNum($l['net_n1'] ?? 0)];
                }
            }
            if (($type === 'bilan' || $type === 'comparatif') && isset($bilanData['resultat_exercice'])) {
                $rows[] = ['=== RÉSULTAT NET DE L\'EXERCICE', '', '', '', $this->export->formatNum($bilanData['resultat_exercice']), $this->export->formatNum($bilanData['total_actif_n1'] ?? 0)];
            }
            return [$headers, $rows];
        }

        if ($type === 'variation-kp') {
            $headers = ['Libellé', 'Ouverture', 'Variation', 'Clôture'];
            $rows = [];
            foreach ($data['lignes'] ?? [] as $l) {
                $rows[] = [
                    $l['libelle'],
                    $this->export->formatNum($l['ouverture'] ?? 0),
                    $this->export->formatNum($l['variation'] ?? 0),
                    $this->export->formatNum($l['cloture'] ?? 0)
                ];
            }
            return [$headers, $rows];
        }

        $headers = ['Réf', 'Libellé', 'Note', 'Montant N', 'Montant N-1'];
        $rows = [];
        foreach ($data['lignes'] ?? [] as $l) {
            $lib = $l['libelle'] ?? '';
            if (($l['type'] ?? '') === 'titre') $lib = '### ' . $lib;
            if (($l['type'] ?? '') === 'total') $lib = '=== ' . $lib;
            $rows[] = [
                $l['ref'] ?? '',
                $lib,
                $l['note'] ?? '',
                $this->export->formatNum($l['montant_n'] ?? $l['cloture'] ?? $l['ouverture'] ?? 0),
                $this->export->formatNum($l['montant_n1'] ?? 0)
            ];
        }
        return [$headers, $rows];
    }

    protected function exportGlobaux(array $ctx, string $format)
    {
        $societeId = $ctx['societe']->id;
        $exN1 = $ctx['exerciceN1'];
        $sections = [];

        // 1. Bilan
        $bilan = $this->etats->bilan($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1);
        [$h, $r] = $this->formatEtatsRows('bilan', $bilan);
        $sections[] = ['title' => 'BILAN ACTIF / PASSIF', 'headers' => $h, 'rows' => $r];

        // 2. Compte de résultat
        $cr = $this->etats->compteResultat($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1);
        [$h, $r] = $this->formatEtatsRows('compte-resultat', $cr);
        $sections[] = ['title' => 'COMPTE DE RÉSULTAT', 'headers' => $h, 'rows' => $r];

        // 3. Flux de trésorerie
        $tft = $this->etats->fluxTresorerie($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1);
        [$h, $r] = $this->formatEtatsRows('flux-tresorerie', $tft);
        $sections[] = ['title' => 'TABLEAU DES FLUX DE TRÉSORERIE (TFT)', 'headers' => $h, 'rows' => $r];

        // 4. Variation KP
        $vkp = $this->etats->variationCapitauxPropres($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1);
        [$h, $r] = $this->formatEtatsRows('variation-kp', $vkp);
        $sections[] = ['title' => 'TABLEAU DE VARIATION DES CAPITAUX PROPRES (TVCP)', 'headers' => $h, 'rows' => $r];

        // 5. Comparatif (On réutilise Bilan & CR groupés)
        $comp = $this->etats->comparatif($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode']);
        [$hB, $rB] = $this->formatEtatsRows('bilan', $comp['bilan']);
        [$hC, $rC] = $this->formatEtatsRows('compte-resultat', $comp['compte_resultat']);
        $sections[] = ['title' => 'ÉTAT COMPARATIF N / N-1 (BILAN)', 'headers' => $hB, 'rows' => $rB];
        $sections[] = ['title' => 'ÉTAT COMPARATIF N / N-1 (RÉSULTAT)', 'headers' => $hC, 'rows' => $rC];

        // 6. Annexes
        $annexes = $this->etats->annexes();
        $annexRows = [];
        $annexRows[] = ['### NOTES RELATIVES AUX ÉTATS FINANCIERS', '', ''];
        foreach($annexes['sections'] as $s) {
            $annexRows[] = ['### Note '.$s['num'], $s['titre'], ''];
            $annexRows[] = ['', $s['contenu'], ''];
        }
        $sections[] = ['title' => 'ANNEXES SYSCOHADA', 'headers' => ['Réf Note', 'Libellé / Contenu', ''], 'rows' => $annexRows];

        $meta = [
            'Exercice' => $ctx['exercice']->libelle,
            'Période' => 'Du ' . $ctx['exercice']->date_debut->format('d/m/Y') . ' au ' . date('d/m/Y', strtotime($ctx['dateArrete'])),
            'Devise d\'affichage' => $ctx['devise']
        ];

        $filename = "etats_financiers_complets_" . $ctx['exercice']->libelle;

        if ($format === 'pdf') {
            return $this->export->downloadPdfMulti($sections, $filename, 'DOSSIER ÉTATS FINANCIERS ANNUELS', $meta, $ctx['societe']);
        }

        return $this->export->downloadExcelMulti($sections, $filename, $ctx['societe'], $meta);
    }

    public function fiscalite(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->fiscaliteCtx($request, $societeId);
        if (isset($ctx['error'])) abort(422, $ctx['error']);

        if (in_array($type, ['tva-collectee', 'tva-deductible'], true)) {
            $headers = ['Date', 'Pièce', 'Compte', 'Libellé', 'Base HT', 'TVA', 'Taux'];
            $data = $type === 'tva-collectee'
                ? $this->fiscalite->tvaCollectee($societeId, $ctx['exercice'], $ctx['dateDebut'], $ctx['dateFin'], $ctx['devise'])
                : $this->fiscalite->tvaDeductible($societeId, $ctx['exercice'], $ctx['dateDebut'], $ctx['dateFin'], $ctx['devise']);
            $rows = [];
            foreach ($data['detail'] ?? [] as $l) {
                $rows[] = [$l['date'] ?? '', $l['num_piece'] ?? '', $l['num_compte'] ?? '', $l['libelle'] ?? '', $this->export->formatNum($l['base_ht'] ?? 0), $this->export->formatNum($l['montant_tva'] ?? 0), ($l['taux'] ?? '0') . '%'];
            }
        } elseif ($type === 'is') {
            $headers = ['Libellé', 'Montant'];
            $data = $this->fiscalite->impotSocietes($societeId, $ctx['exercice'], $ctx['dateFin'], $ctx['devise']);
            $rows = [['Résultat comptable', $this->export->formatNum($data['resultat_comptable'] ?? 0)], ['Base imposable', $this->export->formatNum($data['base_imposable'] ?? 0)], ['Taux IS (%)', $data['taux_is'] ?? ''], ['=== MONTANT IS', $this->export->formatNum($data['montant_is'] ?? 0)]];
        } elseif ($type === 'echeances') {
            $headers = ['Échéance', 'Type', 'Période Début', 'Période Fin', 'Date limite', 'Statut'];
            $data = $this->fiscalite->echeances($societeId, $ctx['exercice']);
            $today = now()->toDateString();
            $rows = [];
            foreach ($data as $e) {
                $statut = $e['statut'] ?? 'a_declarer';
                $label = ($statut === 'deposee') ? 'Déposée' : 'À déclarer';
                if ($statut !== 'deposee' && !empty($e['date_limite_depot']) && $e['date_limite_depot'] < $today) {
                    $label = 'EN RETARD';
                }
                $rows[] = [
                    $e['libelle'] ?? '',
                    $e['type'] ?? '',
                    $e['periode_debut'] ?? '',
                    $e['periode_fin'] ?? '',
                    $e['date_limite_depot'] ?? '',
                    $label,
                ];
            }
        } elseif ($type === 'declarations') {
            $headers = ['Type', 'Période Début', 'Période Fin', 'TVA Coll.', 'TVA Déd.', 'Impôt', 'Statut'];
            $query = DeclarationFiscale::where('societe_id', $societeId);
            if ($ctx['exercice']) $query->where('exercice_id', $ctx['exercice']->id);
            if ($ctx['dateDebut']) $query->where('periode_fin', '>=', $ctx['dateDebut']);
            if ($ctx['dateFin']) $query->where('periode_debut', '<=', $ctx['dateFin']);

            $data = $query->orderByDesc('periode_fin')->get();
            $rows = [];
            foreach ($data as $d) {
                $rows[] = [
                    $d->type,
                    $d->periode_debut?->format('d/m/Y') ?? '',
                    $d->periode_fin?->format('d/m/Y') ?? '',
                    $this->export->formatNum($d->tva_collectee),
                    $this->export->formatNum($d->tva_deductible),
                    $this->export->formatNum($d->montant_impot),
                    $d->statut,
                ];
            }
        } elseif ($type === 'dsf') {
            $headers = ['Rubrique', 'Valeur'];
            $data = $this->fiscalite->dsf($societeId, $ctx['exercice'], $ctx['dateFin'], $ctx['devise']);
            $rows = [
                ['Exercice', $data['exercice']],
                ['Date d\'arrêté', $data['date_arrete']],
                ['Total Actif (TA)', $this->export->formatNum($data['bilan_total_actif'])],
                ['Total Passif (TP)', $this->export->formatNum($data['bilan_total_passif'])],
                ['Total Capitaux Propres (TPE)', $this->export->formatNum($data['bilan_total_capitaux_propres'])],
                ['Passif + Equity', $this->export->formatNum($data['bilan_total_passif_et_equity'])],
                ['Chiffre d\'affaires (XB)', $this->export->formatNum($data['chiffre_affaires'])],
                ['Résultat Net (XI)', $this->export->formatNum($data['resultat_net'])],
                ['### SYNTHESE TVA', ''],
                ['TVA Collectée', $this->export->formatNum($data['tva']['tva_collectee'])],
                ['TVA Déductible', $this->export->formatNum($data['tva']['tva_deductible'])],
                ['TVA Nette', $this->export->formatNum($data['tva']['tva_nette'])],
            ];
        } else {
            abort(404);
        }

        return $this->export->respond($format, $headers, $rows, 'fiscalite_'.$type, 'Fiscalité — '.$type, $this->metaFiltres($ctx), $ctx['societe']);
    }

    public function saisie(Request $request, string $format)
    {
        $societeId = SocieteContext::requireId();
        $page = $request->get('page', 'nouvelle');
        $societe = Societe::findOrFail($societeId);

        $journal = $this->saisie->resolveJournal($societeId, $page);

        $query = Ecriture::with('journal:id,code')->parSociete($societeId)->orderByDesc('created_at');

        if ($journal) {
            $query->where('journal_id', $journal->id);
        }

        if ($page === 'devises') {
            $query->where('devise', '!=', $societe->devise_principale ?? 'CDF');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->get('statut'));
        }

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(fn ($q) => $q
                ->where('num_piece', 'like', "%{$search}%")
                ->orWhere('libelle', 'like', "%{$search}%")
                ->orWhere('reference_externe', 'like', "%{$search}%"));
        }

        if ($request->filled('date_debut')) $query->where('date_ecriture', '>=', $request->get('date_debut'));
        if ($request->filled('date_fin')) $query->where('date_ecriture', '<=', $request->get('date_fin'));

        $headers = ['Date enreg.', 'Date écriture', 'Pièce', 'Journal', 'Libellé', 'Débit', 'Crédit', 'Statut'];
        $rows = $query->limit(5000)->get()->map(fn ($e) => [
            $e->created_at?->format('d/m/Y H:i') ?? '',
            $e->date_ecriture?->format('d/m/Y') ?? $e->date_ecriture,
            $e->num_piece, $e->journal?->code ?? '', $e->libelle,
            $this->export->formatNum($e->total_debit), $this->export->formatNum($e->total_credit), $e->statut,
        ])->all();

        $title = "Liste des écritures — " . ucfirst($page);

        $meta = [
            'Journal' => $journal ? "{$journal->code} - {$journal->libelle}" : ($page === 'devises' ? "Multi-devises" : "Tous"),
            'Période' => ($request->get('date_debut') ?: 'Dép.') . " au " . ($request->get('date_fin') ?: 'Auj.'),
            'Statut' => $request->get('statut') ?: 'Tous'
        ];

        return $this->export->respond($format, $headers, $rows, 'ecritures_'.$page, $title, $meta, $societe);
    }

    public function parametres(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();
        $societe = Societe::findOrFail($societeId);
        return match ($type) {
            'plan-comptable' => $this->exportPlanComptable($request, $societeId, $format, $societe),
            'tiers' => $this->exportTiers($request, $societeId, $format, $societe),
            'journaux' => $this->exportJournaux($societeId, $format, $societe),
            default => abort(404),
        };
    }

    protected function rowsGrandLivre(int $societeId, array $f, Request $request): array
    {
        $numCompte = $request->get('num_compte');
        if (!$numCompte) {
            $data = $this->livres->grandLivreGeneral($societeId, $f['exercice']->id, $f['dateDebut'], $f['dateFin'], $f['deviseAffichage'], $f['modeConversion']);
            $headers = ['Compte', 'Libellé', 'Débit', 'Crédit', 'Solde'];
            $rows = collect($data['lignes'] ?? [])->map(fn ($r) => [$r['num_compte'], $r['libelle'], $this->export->formatNum($r['debit']), $this->export->formatNum($r['credit']), $this->export->formatNum($r['solde'])])->all();
            if (isset($data['totaux'])) $rows[] = ['=== TOTAL GÉNÉRAL', '', $this->export->formatNum($data['totaux']['debit']), $this->export->formatNum($data['totaux']['credit']), $this->export->formatNum($data['totaux']['solde'])];
            return [$headers, $rows];
        }
        $data = $this->livres->grandLivre($societeId, $f['exercice']->id, $numCompte, $f['dateDebut'], $f['dateFin'], $f['deviseAffichage'], $f['modeConversion']);
        $headers = ['Date', 'Pièce', 'Libellé', 'Débit', 'Crédit', 'Solde'];
        $rows = collect($data['lignes'] ?? [])->map(fn ($l) => [$l['date_ecriture'] ?? '', $l['num_piece'] ?? '', $l['libelle'] ?? '', $this->export->formatNum($l['debit'] ?? 0), $this->export->formatNum($l['credit'] ?? 0), $this->export->formatNum($l['solde'] ?? 0)])->all();
        $rows[] = ['=== SOLDE DE CLÔTURE', '', '', '', '', $this->export->formatNum($data['solde_cloture'] ?? 0)];
        return [$headers, $rows];
    }

    protected function rowsTresorerie(int $societeId, array $f, Request $request, string $type): array
    {
        $numCompte = $request->get('num_compte');
        if (!$numCompte) {
            $data = $this->livres->syntheseTresorerie($societeId, $f['exercice']->id, $type, $f['dateFin'], $f['deviseAffichage'], $f['modeConversion']);
            $headers = ['Num Compte', 'Libellé', 'Solde Actuel'];
            $rows = collect($data)->map(fn ($r) => [$r['num_compte'], $r['libelle'], $this->export->formatNum($r['solde_actuel'])])->all();
            return [$headers, $rows];
        }
        $data = $this->livres->livreTresorerie($societeId, $f['exercice']->id, $numCompte, $f['dateDebut'], $f['dateFin'], $f['deviseAffichage'], $f['modeConversion'], $type);
        $headers = ['Date', 'Pièce', 'Journal', 'Libellé', 'Partenaire', 'Débit', 'Crédit', 'Solde'];
        $rows = [['Solde d\'ouverture', '', '', '', '', '', '', $this->export->formatNum($data['soldes']['ouverture_jour'] ?? 0)]];
        foreach ($data['lignes'] ?? [] as $l) {
            $rows[] = [$l['date_ecriture'] ?? '', $l['num_piece'] ?? '', $l['journal_code'] ?? '', $l['libelle'] ?? '', $l['partenaire'] ?? '', $this->export->formatNum($l['debit'] ?? 0), $this->export->formatNum($l['credit'] ?? 0), $this->export->formatNum($l['solde'] ?? 0)];
        }
        $rows[] = ['=== SOLDE FINAL', '', '', '', '', '', '', $this->export->formatNum($data['soldes']['final_periode'] ?? 0)];
        return [$headers, $rows];
    }

    protected function rowsBalance(int $societeId, array $f, Request $request): array
    {
        $data = $this->livres->balanceGenerale($societeId, $f['exercice']->id, $f['dateDebut'], $f['dateFin'], $f['deviseAffichage'], $f['modeConversion'], $request->integer('classe') ?: null);
        $headers = ['Compte', 'Intitulé', 'Solde début D', 'Solde début C', 'Mvt débit', 'Mvt crédit', 'Solde fin D', 'Solde fin C'];
        $rows = collect($data['lignes'] ?? [])->map(fn ($r) => [$r['num_compte'] ?? '', $r['libelle'] ?? '', $this->export->formatNum($r['solde_debut_debiteur'] ?? 0), $this->export->formatNum($r['solde_debut_crediteur'] ?? 0), $this->export->formatNum($r['mouvement_debit'] ?? 0), $this->export->formatNum($r['mouvement_credit'] ?? 0), $this->export->formatNum($r['solde_fin_debiteur'] ?? 0), $this->export->formatNum($r['solde_fin_crediteur'] ?? 0)])->all();
        if (isset($data['totaux'])) $rows[] = ['=== TOTAL GÉNÉRAL', '', $this->export->formatNum($data['totaux']['solde_debut_debiteur']), $this->export->formatNum($data['totaux']['solde_debut_crediteur']), $this->export->formatNum($data['totaux']['mouvement_debit']), $this->export->formatNum($data['totaux']['mouvement_credit']), $this->export->formatNum($data['totaux']['solde_fin_debiteur']), $this->export->formatNum($data['totaux']['solde_fin_crediteur'])];
        return [$headers, $rows];
    }

    protected function rowsLettrage(int $societeId, Request $request): array
    {
        $lignes = $this->livres->lettrageNonLettre($societeId, $request->get('num_compte', '41'), $request->integer('tiers_id') ?: null);
        $headers = ['Date', 'Pièce', 'Libellé', 'Débit', 'Crédit', 'Lettré'];
        $rows = $lignes->map(fn ($l) => [$l['date_ecriture'] ?? '', $l['num_piece'] ?? '', $l['libelle'] ?? '', $this->export->formatNum($l['debit'] ?? 0), $this->export->formatNum($l['credit'] ?? 0), ($l['lettre'] ?? '') ? 'Oui' : 'Non'])->all();
        return [$headers, $rows];
    }

    protected function livresFiltres(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);
        $options = $this->livres->optionsDefaut($societe);
        return ['societe' => $societe, 'exercice' => $exercice, 'dateDebut' => $request->get('date_debut', $exercice?->date_debut?->format('Y-m-d')), 'dateFin' => $request->get('date_fin', $exercice?->date_fin?->format('Y-m-d')), 'deviseAffichage' => strtoupper($request->get('devise_affichage', $options['devise_affichage'])), 'modeConversion' => $request->get('mode_conversion', $options['mode_conversion'])];
    }

    protected function metaFiltres(array $f): array
    {
        return array_filter(['Société' => $f['societe']->raison_sociale ?? '', 'Période' => ($f['dateDebut'] ?? '').' au '.($f['dateFin'] ?? ''), 'Devise' => $f['deviseAffichage'] ?? '']);
    }

    protected function fiscaliteCtx(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->fiscalite->exerciceCourant($societeId);
        if ($request->filled('exercice_id')) $exercice = Exercice::where('societe_id', $societeId)->findOrFail($request->integer('exercice_id'));
        if (!$exercice) return ['error' => 'Aucun exercice courant.'];
        $options = $this->livres->optionsDefaut($societe);
        return ['societe' => $societe, 'exercice' => $exercice, 'dateDebut' => $request->get('date_debut', $exercice->date_debut?->format('Y-m-d')), 'dateFin' => $request->get('date_fin', $exercice->date_fin?->format('Y-m-d')), 'devise' => strtoupper($request->get('devise_affichage', $options['devise_affichage']))];
    }

    protected function etatsCtx(Request $request, int $societeId): array
    {
        $exercice = $this->livres->exerciceCourant($societeId);
        if ($request->filled('exercice_id')) $exercice = Exercice::where('societe_id', $societeId)->findOrFail($request->integer('exercice_id'));
        if (!$exercice) return ['error' => 'Aucun exercice.'];
        $societe = Societe::findOrFail($societeId);
        $options = $this->livres->optionsDefaut($societe);
        $exerciceN1 = $this->etats->exercicePrecedent($societeId, $exercice);
        return ['societe' => $societe, 'exercice' => $exercice, 'exerciceN1' => $exerciceN1, 'dateArrete' => $request->get('date_arrete', $exercice->date_fin?->format('Y-m-d')), 'devise' => strtoupper($request->get('devise_affichage', $options['devise_affichage'])), 'mode' => $request->get('mode_conversion', $options['mode_conversion'])];
    }

    protected function exportPlanComptable(Request $request, int $societeId, string $format, Societe $societe)
    {
        $q = PlanComptable::parSociete($societeId)->actif()->orderBy('num_compte');
        $headers = ['Compte', 'Libellé', 'Classe', 'Type'];
        $rows = $q->get()->map(fn ($c) => [$c->num_compte, $c->libelle, $c->classe, $c->type_compte ?? ''])->all();
        return $this->export->respond($format, $headers, $rows, 'plan_comptable', 'Plan comptable', [], $societe);
    }

    protected function exportTiers(Request $request, int $societeId, string $format, Societe $societe)
    {
        $q = Tiers::where('societe_id', $societeId)->orderBy('nom');
        $headers = ['Code', 'Nom', 'Type', 'Compte'];
        $rows = $q->get()->map(fn ($t) => [$t->code ?? '', $t->nom, $t->type, $t->num_compte_collectif ?? ''])->all();
        return $this->export->respond($format, $headers, $rows, 'tiers', 'Fichier des tiers', [], $societe);
    }

    protected function exportJournaux(int $societeId, string $format, Societe $societe)
    {
        $headers = ['Code', 'Libellé', 'Type'];
        $rows = Journal::where('societe_id', $societeId)->orderBy('code')->get()->map(fn ($j) => [$j->code, $j->libelle, $j->type])->all();
        return $this->export->respond($format, $headers, $rows, 'journaux', 'Journaux', [], $societe);
    }

    protected function exportUsers(Request $request, string $format)
    {
        $headers = ['Nom', 'Email'];
        $rows = User::orderBy('name')->get()->map(fn ($u) => [$u->name, $u->email])->all();
        return $this->export->respond($format, $headers, $rows, 'utilisateurs', 'Liste des utilisateurs', []);
    }

    protected function exportRoles(string $format)
    {
        $headers = ['Rôle'];
        $rows = Role::orderBy('name')->get()->map(fn ($r) => [$r->name])->all();
        return $this->export->respond($format, $headers, $rows, 'roles', 'Rôles', []);
    }

    protected function exportAuditLogs(Request $request, string $format)
    {
        $headers = ['Date', 'Utilisateur', 'Action', 'Entité'];
        $rows = AuditLog::with('user:id,name')->orderByDesc('created_at')->limit(1000)->get()->map(fn ($l) => [$l->created_at->format('d/m/Y H:i'), $l->user?->name ?? 'Système', $l->action, $l->entity_type])->all();
        return $this->export->respond($format, $headers, $rows, 'audit_logs', 'Journal d\'audit', []);
    }
}
