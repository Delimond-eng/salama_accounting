<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ParametresSeeder
 * Peuple la table parametres_systeme avec la configuration globale SYSCOHADA.
 * Ces paramètres sont lisibles via : ParametreSysteme::get('cle')
 */
class ParametresSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [

            // ─── SYSCOHADA ────────────────────────────────────────────────────
            ['cle' => 'syscohada_version',          'valeur' => 'Acte Uniforme révisé 2017',    'type_valeur' => 'string',  'groupe' => 'syscohada',   'libelle' => 'Version SYSCOHADA'],
            ['cle' => 'syscohada_date_application', 'valeur' => '2018-01-01',                   'type_valeur' => 'date',    'groupe' => 'syscohada',   'libelle' => 'Date d\'application'],
            ['cle' => 'syscohada_nb_classes',       'valeur' => '9',                             'type_valeur' => 'int',     'groupe' => 'syscohada',   'libelle' => 'Nombre de classes'],
            ['cle' => 'duree_exercice_mois',        'valeur' => '12',                            'type_valeur' => 'int',     'groupe' => 'syscohada',   'libelle' => 'Durée exercice (mois)'],

            // ─── TVA ──────────────────────────────────────────────────────────
            ['cle' => 'tva_taux_normal',            'valeur' => '18',                            'type_valeur' => 'decimal', 'groupe' => 'fiscalite',   'libelle' => 'Taux TVA normal (%)'],
            ['cle' => 'tva_taux_reduit',            'valeur' => '9',                             'type_valeur' => 'decimal', 'groupe' => 'fiscalite',   'libelle' => 'Taux TVA réduit (%)'],
            ['cle' => 'tva_compte_collectee',       'valeur' => '443100',                        'type_valeur' => 'string',  'groupe' => 'fiscalite',   'libelle' => 'Compte TVA collectée'],
            ['cle' => 'tva_compte_deductible',      'valeur' => '445400',                        'type_valeur' => 'string',  'groupe' => 'fiscalite',   'libelle' => 'Compte TVA déductible'],
            ['cle' => 'tva_periodicite',            'valeur' => 'mensuelle',                     'type_valeur' => 'string',  'groupe' => 'fiscalite',   'libelle' => 'Périodicité déclaration TVA'],

            // ─── DEVISES ──────────────────────────────────────────────────────
            ['cle' => 'devise_principale_defaut',   'valeur' => 'XOF',                           'type_valeur' => 'string',  'groupe' => 'devises',     'libelle' => 'Devise principale par défaut'],
            ['cle' => 'nb_decimales_montants',      'valeur' => '0',                             'type_valeur' => 'int',     'groupe' => 'devises',     'libelle' => 'Nombre de décimales (XOF)'],

            // ─── COMPTES SYSTEME ──────────────────────────────────────────────
            ['cle' => 'compte_resultat_benefice',   'valeur' => '131',                           'type_valeur' => 'string',  'groupe' => 'comptes',     'libelle' => 'Compte résultat net bénéfice'],
            ['cle' => 'compte_resultat_perte',      'valeur' => '139',                           'type_valeur' => 'string',  'groupe' => 'comptes',     'libelle' => 'Compte résultat net perte'],
            ['cle' => 'compte_report_nouveau_B',    'valeur' => '121',                           'type_valeur' => 'string',  'groupe' => 'comptes',     'libelle' => 'Compte report à nouveau (bénéfice)'],
            ['cle' => 'compte_report_nouveau_P',    'valeur' => '129',                           'type_valeur' => 'string',  'groupe' => 'comptes',     'libelle' => 'Compte report à nouveau (perte)'],
            ['cle' => 'compte_ecart_conversion_A',  'valeur' => '476',                           'type_valeur' => 'string',  'groupe' => 'comptes',     'libelle' => 'Compte écart conversion actif (476)'],
            ['cle' => 'compte_ecart_conversion_P',  'valeur' => '477',                           'type_valeur' => 'string',  'groupe' => 'comptes',     'libelle' => 'Compte écart conversion passif (477)'],

            // ─── NUMÉROTATION ─────────────────────────────────────────────────
            ['cle' => 'format_num_piece',           'valeur' => '{PREFIX}{ANNEE}-{NUM}',         'type_valeur' => 'string',  'groupe' => 'numerotation','libelle' => 'Format numérotation pièces'],
            ['cle' => 'numerotation_continue',      'valeur' => 'false',                         'type_valeur' => 'bool',    'groupe' => 'numerotation','libelle' => 'Numérotation continue (non annuelle)'],

            // ─── INTERFACE ────────────────────────────────────────────────────
            ['cle' => 'nb_lignes_par_page',         'valeur' => '25',                            'type_valeur' => 'int',     'groupe' => 'interface',   'libelle' => 'Lignes par page (tableaux)'],
            ['cle' => 'date_format_affichage',      'valeur' => 'd/m/Y',                         'type_valeur' => 'string',  'groupe' => 'interface',   'libelle' => 'Format date affichage'],
            ['cle' => 'langue_defaut',              'valeur' => 'fr',                            'type_valeur' => 'string',  'groupe' => 'interface',   'libelle' => 'Langue par défaut'],

            // ─── ALERTES ─────────────────────────────────────────────────────
            ['cle' => 'alerte_echeance_jours',      'valeur' => '7',                             'type_valeur' => 'int',     'groupe' => 'alertes',     'libelle' => 'Alerte échéance (jours avant)'],
            ['cle' => 'alerte_declaration_jours',   'valeur' => '10',                            'type_valeur' => 'int',     'groupe' => 'alertes',     'libelle' => 'Alerte déclaration (jours avant)'],
        ];

        foreach ($parametres as $param) {
            DB::table('parametres_systeme')->updateOrInsert(
                ['cle' => $param['cle']],
                array_merge($param, [
                    'modifiable' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ ' . count($parametres) . ' paramètres système insérés.');
    }
}
