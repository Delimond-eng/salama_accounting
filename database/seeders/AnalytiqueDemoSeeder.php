<?php

namespace Database\Seeders;

use App\Models\AxeAnalytique;
use App\Models\SectionAnalytique;
use App\Models\Societe;
use Illuminate\Database\Seeder;

class AnalytiqueDemoSeeder extends Seeder
{
    public function run(): void
    {
        $societe = Societe::query()->first();
        if (! $societe) {
            return;
        }

        $axes = [
            ['code' => 'PROJET', 'libelle' => 'Projet', 'ordre_affichage' => 1, 'sections' => [
                ['code' => 'SALAMA', 'libelle' => 'Projet Salama'],
                ['code' => 'RAWBANK', 'libelle' => 'Projet Rawbank'],
            ]],
            ['code' => 'DEPT', 'libelle' => 'Département', 'ordre_affichage' => 2, 'sections' => [
                ['code' => 'TECH', 'libelle' => 'Technique'],
                ['code' => 'COMPTA', 'libelle' => 'Comptabilité'],
            ]],
            ['code' => 'SITE', 'libelle' => 'Site', 'ordre_affichage' => 3, 'sections' => [
                ['code' => 'KIN', 'libelle' => 'Kinshasa'],
                ['code' => 'LUB', 'libelle' => 'Lubumbashi'],
            ]],
        ];

        foreach ($axes as $axeData) {
            $sections = $axeData['sections'];
            unset($axeData['sections']);

            $axe = AxeAnalytique::firstOrCreate(
                ['societe_id' => $societe->id, 'code' => $axeData['code']],
                array_merge($axeData, ['societe_id' => $societe->id, 'actif' => true])
            );

            foreach ($sections as $s) {
                SectionAnalytique::firstOrCreate(
                    ['axe_analytique_id' => $axe->id, 'code' => $s['code']],
                    array_merge($s, ['societe_id' => $societe->id, 'axe_analytique_id' => $axe->id, 'actif' => true])
                );
            }
        }
    }
}
