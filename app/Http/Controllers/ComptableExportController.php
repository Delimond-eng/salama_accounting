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
        if (! $f['exercice']) {
            abort(422, 'Aucun exercice courant.');
        }

        $title = config("accounting_exports.livres.{$type}.title", "Livre comptable");
        $meta = $this->metaFiltres($f);

        [$headers, $rows] = match ($type) {
            'balance' => $this->rowsBalance($societeId, $f, $request),
            'journal' => $this->rowsJournal($societeId, $f, $request),
            'grand-livre' => $this->rowsGrandLivre($societeId, $f, $request),
            'auxiliaire' => $this->rowsAuxiliaire($societeId, $f, $request),
            'comptes-tiers' => $this->rowsComptesTiers($societeId, $f),
            'tresorerie' => $this->rowsTresorerie($societeId, $f, $request),
            'lettrage' => $this->rowsLettrage($societeId, $request),
            default => abort(404),
        };

        return $this->export->respond($format, $headers, $rows, "livre_{$type}", $title, $meta);
    }

    public function saisie(Request $request, string $format)
    {
        $societeId = SocieteContext::requireId();
        $page = $request->get('page', 'nouvelle');
        $journal = $this->saisie->resolveJournal($societeId, $page, $request->integer('journal_id') ?: null);

        $query = Ecriture::with('journal:id,code')
            ->parSociete($societeId)
            ->orderByDesc('created_at');

        if ($journal) {
            $query->where('journal_id', $journal->id);
        }
        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(fn ($q) => $q
                ->where('num_piece', 'like', "%{$search}%")
                ->orWhere('libelle', 'like', "%{$search}%"));
        }
        if ($request->filled('date_debut')) {
            $query->where('date_ecriture', '>=', $request->get('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->where('date_ecriture', '<=', $request->get('date_fin'));
        }

        $headers = ['Date enreg.', 'Date écriture', 'Pièce', 'Journal', 'Libellé', 'Devise', 'Débit', 'Crédit', 'Statut'];
        $rows = $query->limit(5000)->get()->map(fn ($e) => [
            $e->created_at?->format('d/m/Y H:i') ?? '',
            $e->date_ecriture?->format('d/m/Y') ?? $e->date_ecriture,
            $e->num_piece,
            $e->journal?->code ?? '',
            $e->libelle,
            $e->devise ?? 'CDF',
            $this->export->formatNum($e->total_debit),
            $this->export->formatNum($e->total_credit),
            $e->statut,
        ])->all();

        $societe = Societe::find($societeId);

        return $this->export->respond(
            $format,
            $headers,
            $rows,
            'ecritures_'.$page,
            'Liste des écritures — '.$page,
            ['Société' => $societe?->raison_sociale ?? '']
        );
    }

    public function fiscalite(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->fiscaliteCtx($request, $societeId);
        if (isset($ctx['error'])) {
            abort(422, $ctx['error']);
        }

        $headers = ['Date', 'Pièce', 'Compte', 'Libellé', 'Base HT', 'TVA', 'Taux'];
        $rows = [];

        if (in_array($type, ['tva-collectee', 'tva-deductible'], true)) {
            $data = $type === 'tva-collectee'
                ? $this->fiscalite->tvaCollectee($societeId, $ctx['exercice'], $ctx['dateDebut'], $ctx['dateFin'], $ctx['devise'])
                : $this->fiscalite->tvaDeductible($societeId, $ctx['exercice'], $ctx['dateDebut'], $ctx['dateFin'], $ctx['devise']);

            foreach ($data['detail'] ?? [] as $l) {
                $rows[] = [
                    '',
                    '',
                    $l['num_compte'] ?? '',
                    '',
                    '',
                    $this->export->formatNum($l['montant'] ?? 0),
                    '',
                ];
            }
        } elseif ($type === 'declarations') {
            $headers = ['Type', 'Période début', 'Période fin', 'Montant', 'Statut'];
            $decls = \App\Models\DeclarationFiscale::where('societe_id', $societeId)
                ->orderByDesc('periode_fin')->get();
            foreach ($decls as $d) {
                $rows[] = [
                    $d->type_declaration ?? '',
                    $d->periode_debut?->format('d/m/Y') ?? '',
                    $d->periode_fin?->format('d/m/Y') ?? '',
                    $this->export->formatNum($d->montant ?? 0),
                    $d->statut ?? '',
                ];
            }
        } elseif ($type === 'is') {
            $headers = ['Libellé', 'Montant'];
            $data = $this->fiscalite->impotSocietes(
                $societeId,
                $ctx['exercice'],
                $ctx['dateFin'],
                $ctx['devise'],
                $request->get('mode_conversion', 'origine')
            );
            $rows = [
                ['Résultat comptable', $this->export->formatNum($data['resultat_comptable'] ?? 0)],
                ['Base imposable', $this->export->formatNum($data['base_imposable'] ?? 0)],
                ['Taux IS (%)', $data['taux_is'] ?? ''],
                ['Montant IS', $this->export->formatNum($data['montant_is'] ?? 0)],
            ];
        } elseif ($type === 'dsf') {
            $headers = ['Indicateur', 'Valeur'];
            $data = $this->fiscalite->dsf(
                $societeId,
                $ctx['exercice'],
                $ctx['dateFin'],
                $ctx['devise'],
                $request->get('mode_conversion', 'origine')
            );
            $rows = [
                ['Exercice', $data['exercice'] ?? ''],
                ['Date arrêté', $data['date_arrete'] ?? ''],
                ['Total actif', $this->export->formatNum($data['bilan_total_actif'] ?? 0)],
                ['Total passif', $this->export->formatNum($data['bilan_total_passif'] ?? 0)],
                ['Résultat net', $this->export->formatNum($data['resultat_net'] ?? 0)],
                ['Chiffre d\'affaires', $this->export->formatNum($data['chiffre_affaires'] ?? 0)],
            ];
        } elseif ($type === 'echeances') {
            $headers = ['Type', 'Échéance', 'Libellé', 'Statut'];
            foreach ($this->fiscalite->echeances($societeId, $ctx['exercice']) as $e) {
                $rows[] = [
                    $e['type'] ?? '',
                    $e['date_echeance'] ?? '',
                    $e['libelle'] ?? '',
                    $e['statut'] ?? '',
                ];
            }
        } else {
            abort(404);
        }

        return $this->export->respond(
            $format,
            $headers,
            $rows,
            'fiscalite_'.$type,
            'Fiscalité — '.$type,
            $this->metaFiltres($ctx)
        );
    }

    public function parametres(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();

        return match ($type) {
            'plan-comptable' => $this->exportPlanComptable($request, $societeId, $format),
            'tiers' => $this->exportTiers($request, $societeId, $format),
            'journaux' => $this->exportJournaux($societeId, $format),
            default => abort(404),
        };
    }

    public function admin(Request $request, string $type, string $format)
    {
        return match ($type) {
            'users' => $this->exportUsers($request, $format),
            'roles' => $this->exportRoles($format),
            'audit-logs' => $this->exportAuditLogs($request, $format),
            default => abort(404),
        };
    }

    public function etats(Request $request, string $type, string $format)
    {
        $societeId = SocieteContext::requireId();
        $ctx = $this->etatsCtx($request, $societeId);
        if (isset($ctx['error'])) {
            abort(422, $ctx['error']);
        }

        $data = match ($type) {
            'bilan' => $this->etats->bilan($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            'compte-resultat' => $this->etats->compteResultat($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            'flux-tresorerie' => $this->etats->fluxTresorerie($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            'variation-kp' => $this->etats->variationCapitauxPropres($societeId, $ctx['exercice'], $ctx['dateArrete'], $ctx['devise'], $ctx['mode'], $ctx['n1']),
            default => abort(404),
        };

        if ($type === 'bilan') {
            $headers = ['Bloc', 'Réf', 'Libellé', 'Compte', 'Montant N'];
            $rows = [];
            foreach (['actif' => 'ACTIF', 'passif' => 'PASSIF'] as $key => $label) {
                foreach ($data[$key] ?? [] as $l) {
                    $rows[] = [
                        $label,
                        $l['ref'] ?? '',
                        $l['libelle'] ?? '',
                        $l['num_compte'] ?? '',
                        $this->export->formatNum($l['net_n'] ?? 0),
                    ];
                }
            }
        } else {
            $headers = ['Réf', 'Libellé', 'Note', 'Montant N', 'Montant N-1'];
            $rows = [];
            foreach ($data['lignes'] ?? [] as $l) {
                $rows[] = [
                    $l['ref'] ?? '',
                    $l['libelle'] ?? '',
                    $l['note'] ?? '',
                    $this->export->formatNum($l['montant_n'] ?? $l['cloture'] ?? 0),
                    $this->export->formatNum($l['montant_n1'] ?? $l['ouverture'] ?? 0),
                ];
            }
        }

        return $this->export->respond(
            $format,
            $headers,
            $rows,
            'etat_'.$type,
            'État — '.$type,
            ['Date arrêté' => $ctx['dateArrete'], 'Devise' => $ctx['devise']]
        );
    }

    protected function livresFiltres(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->livres->exerciceCourant($societeId);
        $options = $this->livres->optionsDefaut($societe);

        return [
            'societe' => $societe,
            'exercice' => $exercice,
            'dateDebut' => $request->get('date_debut', $exercice?->date_debut?->format('Y-m-d')),
            'dateFin' => $request->get('date_fin', $exercice?->date_fin?->format('Y-m-d')),
            'deviseAffichage' => strtoupper($request->get('devise_affichage', $options['devise_affichage'])),
            'modeConversion' => $request->get('mode_conversion', $options['mode_conversion']),
        ];
    }

    protected function metaFiltres(array $f): array
    {
        return array_filter([
            'Société' => $f['societe']->raison_sociale ?? '',
            'Période' => ($f['dateDebut'] ?? '').' au '.($f['dateFin'] ?? ''),
            'Devise' => $f['deviseAffichage'] ?? '',
        ]);
    }

    protected function rowsBalance(int $societeId, array $f, Request $request): array
    {
        $data = $this->livres->balanceGenerale(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $request->integer('classe') ?: null
        );

        $headers = ['Compte', 'Intitulé', 'Solde début D', 'Solde début C', 'Mvt débit', 'Mvt crédit', 'Solde fin D', 'Solde fin C'];
        $rows = collect($data['lignes'] ?? [])->map(fn ($r) => [
            $r['num_compte'] ?? '',
            $r['libelle'] ?? '',
            $this->export->formatNum($r['solde_debut_debiteur'] ?? 0),
            $this->export->formatNum($r['solde_debut_crediteur'] ?? 0),
            $this->export->formatNum($r['mouvement_debit'] ?? 0),
            $this->export->formatNum($r['mouvement_credit'] ?? 0),
            $this->export->formatNum($r['solde_fin_debiteur'] ?? 0),
            $this->export->formatNum($r['solde_fin_crediteur'] ?? 0),
        ])->all();

        return [$headers, $rows];
    }

    protected function rowsJournal(int $societeId, array $f, Request $request): array
    {
        $lignes = $this->livres->journalGeneral(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $request->integer('journal_id') ?: null
        );

        $headers = ['Date enreg.', 'Date écriture', 'Pièce', 'Journal', 'Compte', 'Libellé', 'Partenaire', 'Débit', 'Crédit', 'Devise'];
        $rows = $lignes->map(fn ($l) => [
            $l['date_enregistrement'] ?? '',
            $l['date_ecriture'] ?? '',
            $l['num_piece'] ?? '',
            $l['journal_code'] ?? '',
            $l['num_compte'] ?? '',
            $l['libelle'] ?? '',
            $l['partenaire'] ?? '',
            $this->export->formatNum($l['debit'] ?? 0),
            $this->export->formatNum($l['credit'] ?? 0),
            $l['devise_saisie'] ?? '',
        ])->all();

        return [$headers, $rows];
    }

    protected function rowsGrandLivre(int $societeId, array $f, Request $request): array
    {
        $numCompte = $request->get('num_compte', '521000');
        $data = $this->livres->grandLivre(
            $societeId,
            $f['exercice']->id,
            $numCompte,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion']
        );

        $headers = ['Date', 'Pièce', 'Libellé', 'Débit', 'Crédit', 'Solde'];
        $rows = collect($data['lignes'] ?? [])->map(fn ($l) => [
            $l['date_ecriture'] ?? '',
            $l['num_piece'] ?? '',
            $l['libelle'] ?? '',
            $this->export->formatNum($l['debit'] ?? 0),
            $this->export->formatNum($l['credit'] ?? 0),
            $this->export->formatNum($l['solde'] ?? 0),
        ])->all();

        return [$headers, $rows];
    }

    protected function rowsAuxiliaire(int $societeId, array $f, Request $request): array
    {
        $lignes = $this->livres->balanceAuxiliaire(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $request->get('type_tiers')
        );

        $headers = ['Tiers', 'Compte', 'Solde début D', 'Solde début C', 'Mvt débit', 'Mvt crédit', 'Solde fin D', 'Solde fin C'];
        $rows = $lignes->map(fn ($r) => [
            $r['tiers_nom'] ?? '',
            $r['num_compte'] ?? '',
            $this->export->formatNum($r['solde_debut_debiteur'] ?? 0),
            $this->export->formatNum($r['solde_debut_crediteur'] ?? 0),
            $this->export->formatNum($r['mouvement_debit'] ?? 0),
            $this->export->formatNum($r['mouvement_credit'] ?? 0),
            $this->export->formatNum($r['solde_fin_debiteur'] ?? 0),
            $this->export->formatNum($r['solde_fin_crediteur'] ?? 0),
        ])->all();

        return [$headers, $rows];
    }

    protected function rowsComptesTiers(int $societeId, array $f): array
    {
        $tiers = $this->livres->comptesTiers($societeId);
        $soldes = $this->livres->balanceAuxiliaire(
            $societeId,
            $f['exercice']->id,
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            null
        )->keyBy('tiers_id');

        $headers = ['Code', 'Nom', 'Type', 'Compte', 'Solde débiteur', 'Solde créditeur'];
        $rows = $tiers->map(function ($t) use ($soldes) {
            $s = $soldes->get($t->id);

            return [
                $t->code ?? '',
                $t->nom ?? '',
                $t->type ?? '',
                $t->num_compte_collectif ?? '',
                $this->export->formatNum($s['solde_fin_debiteur'] ?? 0),
                $this->export->formatNum($s['solde_fin_crediteur'] ?? 0),
            ];
        })->all();

        return [$headers, $rows];
    }

    protected function rowsTresorerie(int $societeId, array $f, Request $request): array
    {
        $validated = $request->validate([
            'num_compte' => 'required|string',
            'type' => 'required|in:banque,caisse',
        ]);

        $data = $this->livres->livreTresorerie(
            $societeId,
            $f['exercice']->id,
            $validated['num_compte'],
            $f['dateDebut'],
            $f['dateFin'],
            $f['deviseAffichage'],
            $f['modeConversion'],
            $validated['type']
        );

        $headers = ['Date enreg.', 'Date écriture', 'Pièce', 'Libellé', 'Débit', 'Crédit', 'Solde'];
        $rows = collect($data['lignes'] ?? [])->map(fn ($l) => [
            $l['date_enregistrement'] ?? '',
            $l['date_ecriture'] ?? '',
            $l['num_piece'] ?? '',
            $l['libelle'] ?? '',
            $this->export->formatNum($l['debit'] ?? 0),
            $this->export->formatNum($l['credit'] ?? 0),
            $this->export->formatNum($l['solde'] ?? 0),
        ])->all();

        return [$headers, $rows];
    }

    protected function rowsLettrage(int $societeId, Request $request): array
    {
        $lignes = $this->livres->lettrageNonLettre(
            $societeId,
            $request->get('num_compte', '41'),
            $request->integer('tiers_id') ?: null
        );

        $headers = ['Date', 'Pièce', 'Libellé', 'Débit', 'Crédit', 'Lettré'];
        $rows = $lignes->map(fn ($l) => [
            $l['date_ecriture'] ?? '',
            $l['num_piece'] ?? '',
            $l['libelle'] ?? '',
            $this->export->formatNum($l['debit'] ?? 0),
            $this->export->formatNum($l['credit'] ?? 0),
            ($l['lettre'] ?? '') ? 'Oui' : 'Non',
        ])->all();

        return [$headers, $rows];
    }

    protected function fiscaliteCtx(Request $request, int $societeId): array
    {
        $societe = Societe::findOrFail($societeId);
        $exercice = $this->fiscalite->exerciceCourant($societeId);
        if ($request->filled('exercice_id')) {
            $exercice = Exercice::where('societe_id', $societeId)->findOrFail($request->integer('exercice_id'));
        }
        $options = $this->livres->optionsDefaut($societe);

        if (! $exercice) {
            return ['error' => 'Aucun exercice courant.'];
        }

        return [
            'societe' => $societe,
            'exercice' => $exercice,
            'dateDebut' => $request->get('date_debut', $exercice->date_debut?->format('Y-m-d')),
            'dateFin' => $request->get('date_fin', $exercice->date_fin?->format('Y-m-d')),
            'devise' => strtoupper($request->get('devise_affichage', $options['devise_affichage'])),
            'deviseAffichage' => strtoupper($request->get('devise_affichage', $options['devise_affichage'])),
        ];
    }

    protected function etatsCtx(Request $request, int $societeId): array
    {
        $exercice = $this->livres->exerciceCourant($societeId);
        if ($request->filled('exercice_id')) {
            $exercice = Exercice::where('societe_id', $societeId)->findOrFail($request->integer('exercice_id'));
        }
        if (! $exercice) {
            return ['error' => 'Aucun exercice.'];
        }

        $societe = Societe::findOrFail($societeId);
        $options = $this->livres->optionsDefaut($societe);

        return [
            'exercice' => $exercice,
            'dateArrete' => $request->get('date_arrete', $exercice->date_fin?->format('Y-m-d')),
            'devise' => strtoupper($request->get('devise_affichage', $options['devise_affichage'])),
            'mode' => $request->get('mode_conversion', $options['mode_conversion']),
            'n1' => $request->boolean('avec_n1', true),
        ];
    }

    protected function exportPlanComptable(Request $request, int $societeId, string $format)
    {
        $q = PlanComptable::parSociete($societeId)->actif()->orderBy('num_compte');
        if ($classe = $request->integer('classe')) {
            $q->where('classe', $classe);
        }
        if ($search = trim((string) $request->get('search', ''))) {
            $q->where(fn ($s) => $s->where('num_compte', 'like', "%{$search}%")->orWhere('libelle', 'like', "%{$search}%"));
        }

        $headers = ['Compte', 'Libellé', 'Classe', 'Type', 'Tiers'];
        $rows = $q->get()->map(fn ($c) => [
            $c->num_compte,
            $c->libelle,
            $c->classe,
            $c->type_compte ?? '',
            $c->est_compte_tiers ? 'Oui' : 'Non',
        ])->all();

        return $this->export->respond($format, $headers, $rows, 'plan_comptable', 'Plan comptable SYSCOHADA', []);
    }

    protected function exportTiers(Request $request, int $societeId, string $format)
    {
        $q = Tiers::where('societe_id', $societeId)->orderBy('nom');
        if ($type = $request->get('type')) {
            $q->where('type', $type);
        }
        if ($search = trim((string) $request->get('search', ''))) {
            $q->where(fn ($s) => $s->where('nom', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        $headers = ['Code', 'Nom', 'Type', 'Compte collectif', 'Ville', 'Téléphone'];
        $rows = $q->get()->map(fn ($t) => [
            $t->code ?? '',
            $t->nom,
            $t->type,
            $t->num_compte_collectif ?? '',
            $t->ville ?? '',
            $t->telephone ?? '',
        ])->all();

        return $this->export->respond($format, $headers, $rows, 'tiers', 'Fichier des tiers', []);
    }

    protected function exportJournaux(int $societeId, string $format)
    {
        $headers = ['Code', 'Libellé', 'Type', 'Actif'];
        $rows = Journal::where('societe_id', $societeId)->orderBy('code')->get()->map(fn ($j) => [
            $j->code,
            $j->libelle,
            $j->type,
            $j->actif ? 'Oui' : 'Non',
        ])->all();

        return $this->export->respond($format, $headers, $rows, 'journaux', 'Journaux comptables', []);
    }

    protected function exportUsers(Request $request, string $format)
    {
        $q = User::query()->with('roles')->orderBy('name');
        if ($search = trim((string) $request->get('search', ''))) {
            $q->where(fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $headers = ['Nom', 'Email', 'Rôles', 'Créé le'];
        $rows = $q->get()->map(fn ($u) => [
            $u->name,
            $u->email,
            $u->roles->pluck('name')->join(', '),
            $u->created_at?->format('d/m/Y H:i') ?? '',
        ])->all();

        return $this->export->respond($format, $headers, $rows, 'utilisateurs', 'Liste des utilisateurs', []);
    }

    protected function exportRoles(string $format)
    {
        $headers = ['Rôle', 'Permissions'];
        $rows = Role::with('permissions')->orderBy('name')->get()->map(fn ($r) => [
            $r->name,
            $r->permissions->pluck('name')->join(', '),
        ])->all();

        return $this->export->respond($format, $headers, $rows, 'roles', 'Rôles et permissions', []);
    }

    protected function exportAuditLogs(Request $request, string $format)
    {
        $q = AuditLog::with('user:id,name')->orderByDesc('created_at')->limit(5000);
        if ($search = trim((string) $request->get('search', ''))) {
            $q->where(fn ($s) => $s
                ->where('action', 'like', "%{$search}%")
                ->orWhere('entity_type', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        $headers = ['Date', 'Utilisateur', 'Action', 'Entité', 'Référence', 'Description'];
        $rows = $q->get()->map(fn ($l) => [
            $l->created_at?->format('d/m/Y H:i') ?? '',
            $l->user?->name ?? '',
            $l->action ?? '',
            $l->entity_type ?? '',
            $l->reference ?? '',
            $l->description ?? '',
        ])->all();

        return $this->export->respond($format, $headers, $rows, 'journal_audit', 'Journal d\'audit', []);
    }
}
