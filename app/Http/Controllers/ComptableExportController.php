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
        protected SaisieComptableService $saisie
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

        $exN1 = $ctx['exerciceN1'] instanceof Exercice ? $ctx['exerciceN1'] : null;
        $data = match ($type) {
            'bilan' => $this->etats->bilan($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'compte-resultat' => $this->etats->compteResultat($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'flux-tresorerie' => $this->etats->fluxTresorerie($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'variation-kp' => $this->etats->variationCapitauxPropres($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $exN1),
            'comparatif' => $this->etats->comparatif($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode']),
            default => abort(404),
        };

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
        } else {
            $headers = ['Réf', 'Libellé', 'Note', 'Montant N', 'Montant N-1'];
            $rows = [];
            foreach ($data['lignes'] ?? [] as $l) {
                $lib = $l['libelle'] ?? '';
                if (($l['type'] ?? '') === 'titre') $lib = '### ' . $lib;
                if (($l['type'] ?? '') === 'total') $lib = '=== ' . $lib;
                $rows[] = [$l['ref'] ?? '', $lib, $l['note'] ?? '', $this->export->formatNum($l['montant_n'] ?? $l['cloture'] ?? 0), $this->export->formatNum($l['montant_n1'] ?? $l['ouverture'] ?? 0)];
            }
        }

        return $this->export->respond($format, $headers, $rows, 'etat_'.$type, 'État — '.$type, ['Date arrêté' => $ctx['dateArrete'], 'Devise' => $ctx['devise']], $ctx['societe']);
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
        } else {
            abort(404);
        }

        return $this->export->respond($format, $headers, $rows, 'fiscalite_'.$type, 'Fiscalité — '.$type, $this->metaFiltres($ctx), $ctx['societe']);
    }

    public function saisie(Request $request, string $format)
    {
        $societeId = SocieteContext::requireId();
        $page = $request->get('page', 'nouvelle');
        $query = Ecriture::with('journal:id,code')->parSociete($societeId)->orderByDesc('created_at');
        if ($request->filled('date_debut')) $query->where('date_ecriture', '>=', $request->get('date_debut'));
        if ($request->filled('date_fin')) $query->where('date_ecriture', '<=', $request->get('date_fin'));

        $headers = ['Date enreg.', 'Date écriture', 'Pièce', 'Journal', 'Libellé', 'Débit', 'Crédit', 'Statut'];
        $rows = $query->limit(5000)->get()->map(fn ($e) => [
            $e->created_at?->format('d/m/Y H:i') ?? '',
            $e->date_ecriture?->format('d/m/Y') ?? $e->date_ecriture,
            $e->num_piece, $e->journal?->code ?? '', $e->libelle,
            $this->export->formatNum($e->total_debit), $this->export->formatNum($e->total_credit), $e->statut,
        ])->all();

        $societe = Societe::find($societeId);
        return $this->export->respond($format, $headers, $rows, 'ecritures_'.$page, 'Liste des écritures — '.$page, [], $societe);
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

    protected function rowsJournal(int $societeId, array $f, Request $request): array
    {
        $lignes = $this->livres->journalGeneral($societeId, $f['exercice']->id, $f['dateDebut'], $f['dateFin'], $f['deviseAffichage'], $f['modeConversion'], $request->integer('journal_id') ?: null);
        $headers = ['Date enreg.', 'Date écriture', 'Pièce', 'Journal', 'Compte', 'Libellé', 'Partenaire', 'Débit', 'Crédit', 'Devise'];
        $rows = $lignes->map(fn ($l) => [$l['date_enregistrement'] ?? '', $l['date_ecriture'] ?? '', $l['num_piece'] ?? '', $l['journal_code'] ?? '', $l['num_compte'] ?? '', $l['libelle'] ?? '', $l['partenaire'] ?? '', $this->export->formatNum($l['debit'] ?? 0), $this->export->formatNum($l['credit'] ?? 0), $l['devise_saisie'] ?? ''])->all();
        $rows[] = ['=== TOTAL GÉNÉRAL', '', '', '', '', '', '', $this->export->formatNum($lignes->sum('debit')), $this->export->formatNum($lignes->sum('credit')), ''];
        return [$headers, $rows];
    }

    protected function rowsAuxiliaire(int $societeId, array $f, Request $request): array
    {
        $lignes = $this->livres->balanceAuxiliaire($societeId, $f['exercice']->id, $f['dateDebut'], $f['dateFin'], $f['deviseAffichage'], $f['modeConversion'], $request->get('type_tiers'));
        $headers = ['Tiers', 'Compte', 'Solde début D', 'Solde début C', 'Mvt débit', 'Mvt crédit', 'Solde fin D', 'Solde fin C'];
        $rows = $lignes->map(fn ($r) => [$r['nom'] ?? '', $r['num_compte'] ?? '', $this->export->formatNum($r['solde_debut_debiteur'] ?? 0), $this->export->formatNum($r['solde_debut_crediteur'] ?? 0), $this->export->formatNum($r['mouvement_debit'] ?? 0), $this->export->formatNum($r['mouvement_credit'] ?? 0), $this->export->formatNum($r['solde_fin_debiteur'] ?? 0), $this->export->formatNum($r['solde_fin_crediteur'] ?? 0)])->all();
        $rows[] = ['=== TOTAL GÉNÉRAL', '', $this->export->formatNum($lignes->sum('solde_debut_debiteur')), $this->export->formatNum($lignes->sum('solde_debut_crediteur')), $this->export->formatNum($lignes->sum('mouvement_debit')), $this->export->formatNum($lignes->sum('mouvement_credit')), $this->export->formatNum($lignes->sum('solde_fin_debiteur')), $this->export->formatNum($lignes->sum('solde_fin_crediteur'))];
        return [$headers, $rows];
    }

    protected function rowsComptesTiers(int $societeId, array $f): array
    {
        $tiers = $this->livres->comptesTiers($societeId);
        $soldes = $this->livres->balanceAuxiliaire($societeId, $f['exercice']->id, $f['dateDebut'], $f['dateFin'], $f['deviseAffichage'], $f['modeConversion'], null)->keyBy('tiers_id');
        $headers = ['Code', 'Nom', 'Type', 'Compte', 'Solde débiteur', 'Solde créditeur'];
        $rows = $tiers->map(function ($t) use ($soldes) {
            $s = $soldes->get($t->id);
            return [$t->code ?? '', $t->nom ?? '', $t->type ?? '', $t->num_compte_collectif ?? '', $this->export->formatNum($s['solde_fin_debiteur'] ?? 0), $this->export->formatNum($s['solde_fin_crediteur'] ?? 0)];
        })->all();
        $rows[] = ['=== TOTAL GÉNÉRAL', '', '', '', $this->export->formatNum($soldes->sum('solde_fin_debiteur')), $this->export->formatNum($soldes->sum('solde_fin_crediteur'))];
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
