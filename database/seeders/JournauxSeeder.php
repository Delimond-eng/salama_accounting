<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * JournauxSeeder
 *
 * Crée les journaux SYSCOHADA obligatoires + journaux utiles pour chaque société.
 *
 * Ce seeder est conçu pour être appelé APRÈS la création d'une société.
 * Il crée les journaux pour TOUTES les sociétés existantes qui n'ont pas encore de journaux.
 *
 * Journaux SYSCOHADA obligatoires :
 *   HA  — Journal des achats
 *   VT  — Journal des ventes
 *   BQ  — Journal de banque (compte principal)
 *   CA  — Journal de caisse
 *   OD  — Opérations diverses (salaires, amortissements, provisions, régularisations...)
 *
 * Journaux supplémentaires recommandés :
 *   SA  — Journal des salaires
 *   IM  — Journal des immobilisations
 *   EF  — Journal des effets (à recevoir / à payer)
 *   AN  — Journal d'à-nouveau (ouverture exercice)
 *   CL  — Journal de clôture
 *   SI  — Journal de simulation / budget
 */
class JournauxSeeder extends Seeder
{
    /**
     * Templates des journaux à créer pour chaque société.
     * La colonne 'compte_contrepartie' utilise les comptes SYSCOHADA standards.
     */
    private function getTemplates(): array
    {
        return [

            // ─── JOURNAUX OBLIGATOIRES SYSCOHADA ─────────────────────────────

            [
                'code'                      => 'HA',
                'libelle'                   => 'Journal des achats',
                'type'                      => 'achats',
                'compte_contrepartie'       => '401',    // Fournisseurs
                'prefixe_piece'             => 'HA-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 5,        // HA-2024-00001
                'saisie_tiers_obligatoire'  => true,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 1,
            ],
            [
                'code'                      => 'VT',
                'libelle'                   => 'Journal des ventes',
                'type'                      => 'ventes',
                'compte_contrepartie'       => '411',    // Clients
                'prefixe_piece'             => 'VT-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 5,
                'saisie_tiers_obligatoire'  => true,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 2,
            ],
            [
                'code'                      => 'BQ',
                'libelle'                   => 'Journal de banque',
                'type'                      => 'banque',
                'compte_contrepartie'       => '521',    // Banques (compte courant principal)
                'prefixe_piece'             => 'BQ-',
                'format_numerotation'       => 'mensuel', // Reprend à 1 chaque mois comme les relevés
                'padding_numero'            => 4,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 3,
            ],
            [
                'code'                      => 'CA',
                'libelle'                   => 'Journal de caisse',
                'type'                      => 'caisse',
                'compte_contrepartie'       => '571',    // Caisse siège social
                'prefixe_piece'             => 'CA-',
                'format_numerotation'       => 'mensuel',
                'padding_numero'            => 4,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 4,
            ],
            [
                'code'                      => 'OD',
                'libelle'                   => 'Opérations diverses',
                'type'                      => 'operations_diverses',
                'compte_contrepartie'       => null,     // Pas de contrepartie automatique pour les OD
                'prefixe_piece'             => 'OD-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 5,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 5,
            ],

            // ─── JOURNAUX SUPPLÉMENTAIRES RECOMMANDÉS ────────────────────────

            [
                'code'                      => 'SA',
                'libelle'                   => 'Journal des salaires',
                'type'                      => 'salaires',
                'compte_contrepartie'       => '422',    // Personnel rémunérations dues
                'prefixe_piece'             => 'SA-',
                'format_numerotation'       => 'mensuel',
                'padding_numero'            => 3,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 6,
            ],
            [
                'code'                      => 'IM',
                'libelle'                   => 'Journal des immobilisations',
                'type'                      => 'immobilisations',
                'compte_contrepartie'       => null,
                'prefixe_piece'             => 'IM-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 4,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 7,
            ],
            [
                'code'                      => 'EF',
                'libelle'                   => 'Journal des effets',
                'type'                      => 'effets',
                'compte_contrepartie'       => null,
                'prefixe_piece'             => 'EF-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 4,
                'saisie_tiers_obligatoire'  => true,
                'saisie_lettrage_auto'      => true,    // Lettrage auto recommandé pour les effets
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => false,   // Désactivé par défaut, à activer si besoin
                'ordre_affichage'           => 8,
            ],
            [
                'code'                      => 'AN',
                'libelle'                   => 'Journal d\'à-nouveau',
                'type'                      => 'ouverture',
                'compte_contrepartie'       => null,
                'prefixe_piece'             => 'AN-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 3,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 9,
            ],
            [
                'code'                      => 'CL',
                'libelle'                   => 'Journal de clôture',
                'type'                      => 'cloture',
                'compte_contrepartie'       => null,
                'prefixe_piece'             => 'CL-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 3,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => false,
                'devise_defaut'             => null,
                'actif'                     => true,
                'ordre_affichage'           => 10,
            ],
            [
                'code'                      => 'SI',
                'libelle'                   => 'Journal de simulation',
                'type'                      => 'simulation',
                'compte_contrepartie'       => null,
                'prefixe_piece'             => 'SI-',
                'format_numerotation'       => 'annuel',
                'padding_numero'            => 4,
                'saisie_tiers_obligatoire'  => false,
                'saisie_lettrage_auto'      => false,
                'mode_brouillard'           => true,    // Ce journal est toujours en brouillard
                'devise_defaut'             => null,
                'actif'                     => false,   // Désactivé par défaut
                'ordre_affichage'           => 11,
            ],
        ];
    }

    public function run(): void
    {
        $societes = DB::table('societes')->where('statut', 'active')->get();

        if ($societes->isEmpty()) {
            $this->command->warn('⚠️  Aucune société active trouvée. Créez une société d\'abord.');
            $this->command->info('   Conseil : php artisan db:seed --class=SocieteSeeder');
            return;
        }

        $templates  = $this->getTemplates();
        $totalCreés = 0;
        $totalIgnorés = 0;

        foreach ($societes as $societe) {
            $this->command->info("📂 Société : {$societe->raison_sociale} (ID: {$societe->id})");

            foreach ($templates as $template) {
                // Vérifier si ce journal existe déjà pour cette société
                $exists = DB::table('journaux')
                    ->where('societe_id', $societe->id)
                    ->where('code', $template['code'])
                    ->exists();

                if ($exists) {
                    $totalIgnorés++;
                    $this->command->line("   ⏭  {$template['code']} — {$template['libelle']} (déjà existant)");
                    continue;
                }

                DB::table('journaux')->insert(array_merge(
                    $template,
                    [
                        'societe_id'   => $societe->id,
                        'prochain_numero' => 1,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]
                ));

                $icon = $template['actif'] ? '✅' : '⚪';
                $this->command->line("   {$icon} {$template['code']} — {$template['libelle']}");
                $totalCreés++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ {$totalCreés} journaux créés, {$totalIgnorés} déjà existants.");
        $this->command->info('   Journaux désactivés par défaut : EF (effets), SI (simulation)');
        $this->command->info('   → Activez-les dans Paramètres > Journaux si nécessaire.');
    }
}
