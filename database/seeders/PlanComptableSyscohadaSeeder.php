<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanComptableSyscohadaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/plan_comptable_syscohada.tsv');
        if (! is_readable($path)) {
            $this->command?->error('Fichier TSV introuvable : '.$path);

            return;
        }

        $exists = DB::table('plan_comptable')->whereNull('societe_id')->exists();
        if ($exists) {
            $this->command?->warn('Plan SYSCOHADA déjà chargé — ignoré.');

            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle, 0, "\t"); // en-tête

        $rows = [];
        $now = now();

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            if (count($row) < 2) {
                continue;
            }
            $num = trim($row[0]);
            $libelle = trim($row[1]);
            $typeDetail = isset($row[2]) ? trim($row[2]) : null;
            $rappro = isset($row[3]) ? strtoupper(trim($row[3])) === 'VRAI' : false;
            $classe = (int) substr($num, 0, 1);

            $rows[] = [
                'societe_id' => null,
                'num_compte' => $num,
                'libelle' => $libelle,
                'classe' => $classe,
                'num_compte_parent' => $this->parentAccount($num),
                'niveau' => $this->niveau($num),
                'type_compte' => $this->typeCompte($classe),
                'type_compte_detail' => $typeDetail,
                'sens_normal' => in_array($classe, [1, 4, 7], true) ? 'crediteur' : 'debiteur',
                'categorie_bilan' => 'non_applicable',
                'est_compte_detail' => true,
                'est_compte_tiers' => $classe === 4,
                'est_lettrable' => $classe === 4,
                'est_rapprochable' => $rappro,
                'est_budgetaire' => false,
                'exige_piece_jointe' => false,
                'multi_devises' => false,
                'exige_analytique' => false,
                'type_tva' => 'non_soumis',
                'actif' => true,
                'est_systeme' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        fclose($handle);

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('plan_comptable')->insert($chunk);
        }

        $this->command?->info('✅ '.count($rows).' comptes SYSCOHADA importés.');
    }

    private function parentAccount(string $num): ?string
    {
        $len = strlen($num);
        if ($len <= 2) {
            return null;
        }

        return substr($num, 0, $len - 2).str_repeat('0', 2);
    }

    private function niveau(string $num): int
    {
        $len = strlen($num);

        return match (true) {
            $len <= 2 => 1,
            $len <= 4 => 2,
            $len <= 6 => 3,
            default => 4,
        };
    }

    private function typeCompte(int $classe): string
    {
        if (in_array($classe, [6, 7, 8], true)) {
            return 'gestion';
        }
        if ($classe === 9) {
            return 'hors_bilan';
        }

        return 'bilan';
    }
}
