<?php

namespace App\Services;

use App\Models\DemandeFonds;
use App\Models\DemandeFondsHistorique;
use App\Models\DemandeFondsValidation;
use App\Models\Paiement;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowEtape;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DemandeFondsService
{
    public function __construct(
        protected FacturationComptableService $comptable,
        protected PaiementFacturationService $paiements,
        protected AuditLogService $audit
    ) {}

    public function workflowDefaut(int $societeId): WorkflowDefinition
    {
        $wf = WorkflowDefinition::parSociete($societeId)
            ->where('type_workflow', 'demande_fonds')
            ->where('est_defaut', true)
            ->where('actif', true)
            ->with('etapes')
            ->first();

        if (! $wf) {
            throw new InvalidArgumentException('Aucun workflow de demande de fonds configuré.');
        }

        return $wf;
    }

    public function genererNumero(int $societeId): string
    {
        $prefix = config('facturation.numerotation.demande_fonds.prefix', 'DF');
        $year = now()->format('Y');
        $last = DemandeFonds::parSociete($societeId)
            ->where('numero', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('numero');
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function creer(int $societeId, array $data): DemandeFonds
    {
        return DB::transaction(function () use ($societeId, $data) {
            $wf = isset($data['workflow_definition_id'])
                ? WorkflowDefinition::parSociete($societeId)->with('etapes')->findOrFail($data['workflow_definition_id'])
                : $this->workflowDefaut($societeId);

            $etapesActives = $wf->etapes->where('actif', true)->sortBy('ordre');
            $premiereEtape = $etapesActives->firstWhere('type_etape', '!=', WorkflowEtape::TYPE_INITIATEUR)
                ?? $etapesActives->first();
            if (! $premiereEtape) {
                throw new InvalidArgumentException('Le workflow ne contient aucune étape active.');
            }

            $demande = DemandeFonds::create([
                'societe_id' => $societeId,
                'workflow_definition_id' => $wf->id,
                'workflow_etape_courante_id' => $premiereEtape->id,
                'numero' => $this->genererNumero($societeId),
                'demandeur_id' => Auth::id(),
                'montant' => $data['montant'],
                'devise' => $data['devise'] ?? 'CDF',
                'motif' => $data['motif'],
                'journal_id' => $data['journal_id'] ?? null,
                'section_analytique_id' => $data['section_analytique_id'] ?? null,
                'statut' => DemandeFonds::STATUT_EN_VALIDATION,
            ]);

            $this->historiser($demande, 'creation', 'Demande créée');
            $this->audit->log('creation', 'demande_fonds', $demande->id, $demande->numero, "Création demande {$demande->numero}", null, $societeId);

            return $demande->fresh(['workflow.etapes', 'etapeCourante', 'demandeur']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function traiterEtape(int $societeId, int $demandeId, string $decision, array $data = []): DemandeFonds
    {
        return DB::transaction(function () use ($societeId, $demandeId, $decision, $data) {
            $demande = DemandeFonds::parSociete($societeId)
                ->with(['workflow.etapes', 'etapeCourante'])
                ->findOrFail($demandeId);

            if (in_array($demande->statut, [DemandeFonds::STATUT_REJETEE, DemandeFonds::STATUT_EXECUTEE], true)) {
                throw new InvalidArgumentException('Cette demande est terminée.');
            }

            $etape = $demande->etapeCourante;
            if (! $etape) {
                throw new InvalidArgumentException('Aucune étape courante.');
            }

            $this->verifierDroitEtape($etape);

            if ($decision === 'rejete') {
                return $this->rejeter($demande, $data['commentaire'] ?? null);
            }

            if ($etape->imputation_comptable) {
                $debit = $data['compte_debit'] ?? null;
                $credit = $data['compte_credit'] ?? null;
                if (! $debit || ! $credit) {
                    throw new InvalidArgumentException('Comptes débit et crédit obligatoires à l\'étape comptable.');
                }
                $demande->update(['compte_debit' => $debit, 'compte_credit' => $credit]);
            }

            DemandeFondsValidation::create([
                'demande_fonds_id' => $demande->id,
                'workflow_etape_id' => $etape->id,
                'user_id' => Auth::id(),
                'decision' => 'approuve',
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            $this->historiser($demande, 'validation_etape', "Étape « {$etape->libelle} » approuvée", ['etape_id' => $etape->id]);
            $this->audit->log('validation_etape', 'demande_fonds', $demande->id, $demande->numero, "Validation étape {$etape->code}", null, $societeId);

            $suivante = $demande->workflow->etapes
                ->where('actif', true)
                ->where('ordre', '>', $etape->ordre)
                ->sortBy('ordre')
                ->first();

            if ($suivante) {
                $demande->update([
                    'workflow_etape_courante_id' => $suivante->id,
                    'statut' => DemandeFonds::STATUT_EN_VALIDATION,
                ]);

                if ($suivante->execution_paiement) {
                    $demande->update(['statut' => DemandeFonds::STATUT_APPROUVEE]);
                }

                return $demande->fresh(['workflow.etapes', 'etapeCourante']);
            }

            $demande->update(['statut' => DemandeFonds::STATUT_APPROUVEE, 'workflow_etape_courante_id' => null]);

            if ($etape->execution_paiement || ($data['executer_paiement'] ?? false)) {
                return $this->executerPaiement($societeId, $demande, $data);
            }

            return $demande->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function executerPaiement(int $societeId, DemandeFonds $demande, array $data = []): DemandeFonds
    {
        if (! $demande->compte_debit || ! $demande->compte_credit) {
            throw new InvalidArgumentException('Imputation comptable requise avant exécution.');
        }

        $methode = $data['methode'] ?? 'caisse';
        $compteTreso = $data['compte_tresorerie']
            ?? ($methode === 'caisse' ? config('facturation.comptes.caisse') : config('facturation.comptes.banque'));

        $libelle = 'Demande de fonds '.$demande->numero.' — '.$demande->motif;
        $date = $data['date_paiement'] ?? now()->toDateString();

        $result = $this->comptable->ecritureDemandeFonds(
            $societeId,
            $demande->compte_debit,
            $compteTreso,
            (float) $demande->montant,
            $libelle,
            $date,
            $demande->devise,
            $demande->journal_id
        );

        $paiement = Paiement::create([
            'societe_id' => $societeId,
            'type_paiement' => 'demande_fonds',
            'demande_fonds_id' => $demande->id,
            'numero' => $this->paiements->genererNumero($societeId),
            'montant' => $demande->montant,
            'devise' => $demande->devise,
            'methode' => $methode,
            'compte_tresorerie' => $compteTreso,
            'date_paiement' => $date,
            'statut' => Paiement::STATUT_VALIDE,
            'ecriture_id' => $result['ecriture']->id,
            'user_id' => Auth::id(),
        ]);

        $demande->update([
            'statut' => DemandeFonds::STATUT_EXECUTEE,
            'ecriture_id' => $result['ecriture']->id,
            'workflow_etape_courante_id' => null,
        ]);

        $this->historiser($demande, 'execution', 'Paiement exécuté', ['paiement_id' => $paiement->id]);
        $this->audit->log('execution', 'demande_fonds', $demande->id, $demande->numero, "Exécution {$demande->numero}", null, $societeId);

        return $demande->fresh(['paiements', 'ecriture']);
    }

    protected function rejeter(DemandeFonds $demande, ?string $motif): DemandeFonds
    {
        $etape = $demande->etapeCourante;

        DemandeFondsValidation::create([
            'demande_fonds_id' => $demande->id,
            'workflow_etape_id' => $etape?->id,
            'user_id' => Auth::id(),
            'decision' => 'rejete',
            'commentaire' => $motif,
        ]);

        $demande->update([
            'statut' => DemandeFonds::STATUT_REJETEE,
            'motif_rejet' => $motif,
            'workflow_etape_courante_id' => null,
        ]);

        $this->historiser($demande, 'rejet', 'Demande rejetée', ['motif' => $motif]);
        $this->audit->log('rejet', 'demande_fonds', $demande->id, $demande->numero, "Rejet {$demande->numero}", ['motif' => $motif], $demande->societe_id);

        return $demande;
    }

    protected function verifierDroitEtape(WorkflowEtape $etape): void
    {
        $user = Auth::user();
        if (! $user) {
            throw new InvalidArgumentException('Utilisateur non authentifié.');
        }

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($etape->role_requis && ! $user->hasRole($etape->role_requis)) {
            throw new InvalidArgumentException("Rôle « {$etape->role_requis} » requis pour cette étape.");
        }

        $permission = match ($etape->type_etape) {
            WorkflowEtape::TYPE_COMPTABLE => 'facturation.process',
            WorkflowEtape::TYPE_CAISSIER => 'facturation.validate',
            WorkflowEtape::TYPE_VALIDATEUR => 'facturation.validate',
            default => 'facturation.view',
        };

        if (! $user->can($permission) && ! $user->can('facturation.process')) {
            throw new InvalidArgumentException('Droits insuffisants pour cette étape.');
        }
    }

    protected function historiser(DemandeFonds $demande, string $action, string $description, ?array $metadata = null): void
    {
        DemandeFondsHistorique::create([
            'demande_fonds_id' => $demande->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
