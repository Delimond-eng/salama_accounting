<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DeviseSeeder
 * Peuple le référentiel ISO 4217 des devises.
 * Inclut toutes les devises de la zone OHADA + devises internationales courantes.
 *
 * Devises OHADA :
 *   XOF — Franc CFA BCEAO (Bénin, Burkina, CI, Guinée-Bissau, Mali, Niger, Sénégal, Togo)
 *   XAF — Franc CFA BEAC (Cameroun, Congo, Gabon, Guinée Éq., RCA, Tchad)
 *   GNF — Franc guinéen
 *   CDF — Franc congolais (RDC)
 *   MGA — Ariary malgache
 *   KMF — Franc comorien
 */
class DeviseSeeder extends Seeder
{
    public function run(): void
    {
        $devises = [

            // ═══════════════════════════════════════════════
            // ZONE OHADA / AFRIQUE SUBSAHARIENNE
            // ═══════════════════════════════════════════════
            [
                'code_iso'            => 'XOF',
                'libelle'             => 'Franc CFA BCEAO',
                'symbole'             => 'FCFA',
                'pays'                => 'Afrique de l\'Ouest (UEMOA)',
                'nb_decimales'        => 0,
                'est_devise_reference'=> false, // devient true selon la société
                'actif'               => true,
            ],
            [
                'code_iso'            => 'XAF',
                'libelle'             => 'Franc CFA BEAC',
                'symbole'             => 'FCFA',
                'pays'                => 'Afrique Centrale (CEMAC)',
                'nb_decimales'        => 0,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'GNF',
                'libelle'             => 'Franc guinéen',
                'symbole'             => 'FG',
                'pays'                => 'Guinée',
                'nb_decimales'        => 0,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'CDF',
                'libelle'             => 'Franc congolais',
                'symbole'             => 'FC',
                'pays'                => 'République Démocratique du Congo',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'MGA',
                'libelle'             => 'Ariary malgache',
                'symbole'             => 'Ar',
                'pays'                => 'Madagascar',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'KMF',
                'libelle'             => 'Franc comorien',
                'symbole'             => 'CF',
                'pays'                => 'Comores',
                'nb_decimales'        => 0,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'NGN',
                'libelle'             => 'Naira nigérian',
                'symbole'             => '₦',
                'pays'                => 'Nigeria',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'GHS',
                'libelle'             => 'Cédi ghanéen',
                'symbole'             => 'GH₵',
                'pays'                => 'Ghana',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'MAD',
                'libelle'             => 'Dirham marocain',
                'symbole'             => 'DH',
                'pays'                => 'Maroc',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'TND',
                'libelle'             => 'Dinar tunisien',
                'symbole'             => 'DT',
                'pays'                => 'Tunisie',
                'nb_decimales'        => 3,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'DZD',
                'libelle'             => 'Dinar algérien',
                'symbole'             => 'DA',
                'pays'                => 'Algérie',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'EGP',
                'libelle'             => 'Livre égyptienne',
                'symbole'             => 'E£',
                'pays'                => 'Égypte',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'KES',
                'libelle'             => 'Shilling kényan',
                'symbole'             => 'KSh',
                'pays'                => 'Kenya',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'TZS',
                'libelle'             => 'Shilling tanzanien',
                'symbole'             => 'TSh',
                'pays'                => 'Tanzanie',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'UGX',
                'libelle'             => 'Shilling ougandais',
                'symbole'             => 'USh',
                'pays'                => 'Ouganda',
                'nb_decimales'        => 0,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'ZAR',
                'libelle'             => 'Rand sud-africain',
                'symbole'             => 'R',
                'pays'                => 'Afrique du Sud',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'ETB',
                'libelle'             => 'Birr éthiopien',
                'symbole'             => 'Br',
                'pays'                => 'Éthiopie',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'RWF',
                'libelle'             => 'Franc rwandais',
                'symbole'             => 'RF',
                'pays'                => 'Rwanda',
                'nb_decimales'        => 0,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],

            // ═══════════════════════════════════════════════
            // DEVISES INTERNATIONALES MAJEURES
            // ═══════════════════════════════════════════════
            [
                'code_iso'            => 'EUR',
                'libelle'             => 'Euro',
                'symbole'             => '€',
                'pays'                => 'Zone Euro',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'USD',
                'libelle'             => 'Dollar américain',
                'symbole'             => '$',
                'pays'                => 'États-Unis',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'GBP',
                'libelle'             => 'Livre sterling',
                'symbole'             => '£',
                'pays'                => 'Royaume-Uni',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'CHF',
                'libelle'             => 'Franc suisse',
                'symbole'             => 'CHF',
                'pays'                => 'Suisse',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'CNY',
                'libelle'             => 'Yuan renminbi',
                'symbole'             => '¥',
                'pays'                => 'Chine',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'JPY',
                'libelle'             => 'Yen japonais',
                'symbole'             => '¥',
                'pays'                => 'Japon',
                'nb_decimales'        => 0,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'CAD',
                'libelle'             => 'Dollar canadien',
                'symbole'             => 'CA$',
                'pays'                => 'Canada',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'AED',
                'libelle'             => 'Dirham des Émirats',
                'symbole'             => 'AED',
                'pays'                => 'Émirats Arabes Unis',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'SAR',
                'libelle'             => 'Riyal saoudien',
                'symbole'             => 'SR',
                'pays'                => 'Arabie Saoudite',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
            [
                'code_iso'            => 'INR',
                'libelle'             => 'Roupie indienne',
                'symbole'             => '₹',
                'pays'                => 'Inde',
                'nb_decimales'        => 2,
                'est_devise_reference'=> false,
                'actif'               => true,
            ],
        ];

        // Upsert : pas de doublons si on relance le seeder
        foreach ($devises as $devise) {
            DB::table('devises')->updateOrInsert(
                ['code_iso' => $devise['code_iso']],
                array_merge($devise, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ ' . count($devises) . ' devises insérées/mises à jour.');
    }
}
