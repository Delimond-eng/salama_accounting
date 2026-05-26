<?php

namespace Database\Seeders;

use App\Models\Exercice;
use App\Models\Societe;
use Illuminate\Database\Seeder;

class SocieteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $annee = (int) date('Y');

        $societe = Societe::updateOrCreate(
            ['code' => 'DEMO'],
            [
                'raison_sociale' => 'Millenium Demo SARL',
                'forme_juridique' => 'SARL',
                'sigle' => 'MILLENIUM',
                'adresse' => '12, Avenue de la Paix',
                'ville' => 'Kinshasa',
                'pays' => 'République Démocratique du Congo',
                'telephone' => '+243 000 000 000',
                'email' => 'demo@millenium-erp.local',
                'regime_fiscal' => 'Réel normal',
                'devise_principale' => 'CDF',
                'statut' => 'active',
            ]
        );

        Exercice::updateOrCreate(
            ['societe_id' => $societe->id, 'annee' => $annee],
            [
                'libelle' => "Exercice {$annee}",
                'date_debut' => "{$annee}-01-01",
                'date_fin' => "{$annee}-12-31",
                'statut' => 'ouvert',
                'est_courant' => true,
            ]
        );

        Exercice::where('societe_id', $societe->id)
            ->where('annee', '!=', $annee)
            ->update(['est_courant' => false]);

        $this->command?->info("✅ Société démo « {$societe->raison_sociale} » (ID {$societe->id}) et exercice {$annee}.");
    }
}
