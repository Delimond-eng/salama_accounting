<?php

namespace Database\Seeders;

use App\Models\Societe;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowEtape;
use Illuminate\Database\Seeder;

class WorkflowDemandeFondsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Societe::where('statut', 'active')->get() as $societe) {
            if (WorkflowDefinition::where('societe_id', $societe->id)->where('code', 'df_standard')->exists()) {
                continue;
            }

            $wf = WorkflowDefinition::create([
                'societe_id' => $societe->id,
                'code' => 'df_standard',
                'libelle' => 'Demande de fonds — circuit standard',
                'type_workflow' => 'demande_fonds',
                'actif' => true,
                'est_defaut' => true,
            ]);

            $etapes = [
                ['ordre' => 1, 'code' => 'init', 'libelle' => 'Initiateur', 'type_etape' => 'initiateur', 'role_requis' => null, 'imputation' => false, 'paiement' => false],
                ['ordre' => 2, 'code' => 'compta', 'libelle' => 'Imputation comptable', 'type_etape' => 'comptable', 'role_requis' => 'comptable', 'imputation' => true, 'paiement' => false],
                ['ordre' => 3, 'code' => 'valid', 'libelle' => 'Validation manager', 'type_etape' => 'validateur', 'role_requis' => 'manager', 'imputation' => false, 'paiement' => false],
                ['ordre' => 4, 'code' => 'caisse', 'libelle' => 'Exécution caisse', 'type_etape' => 'caissier', 'role_requis' => 'caissier', 'imputation' => false, 'paiement' => true],
            ];

            foreach ($etapes as $e) {
                WorkflowEtape::create([
                    'workflow_definition_id' => $wf->id,
                    'ordre' => $e['ordre'],
                    'code' => $e['code'],
                    'libelle' => $e['libelle'],
                    'type_etape' => $e['type_etape'],
                    'role_requis' => $e['role_requis'],
                    'imputation_comptable' => $e['imputation'],
                    'execution_paiement' => $e['paiement'],
                    'actif' => true,
                ]);
            }
        }
    }
}
