<?php

return [
    'devises_autorisees' => ['CDF', 'USD'],

    'taux_tva_defaut' => (float) env('FACTURATION_TVA', 16),

    'numerotation' => [
        'vente_client' => ['prefix' => 'FAC', 'pattern' => '{prefix}-{year}-{seq:4}'],
        'achat_fournisseur' => ['prefix' => 'FAF', 'pattern' => '{prefix}-{year}-{seq:4}'],
        'avoir_client' => ['prefix' => 'AVC', 'pattern' => '{prefix}-{year}-{seq:4}'],
        'avoir_fournisseur' => ['prefix' => 'AVF', 'pattern' => '{prefix}-{year}-{seq:4}'],
        'paiement' => ['prefix' => 'PAY', 'pattern' => '{prefix}-{year}-{seq:4}'],
        'demande_fonds' => ['prefix' => 'DF', 'pattern' => '{prefix}-{year}-{seq:4}'],
    ],

    'comptes' => [
        'client' => '411000',
        'fournisseur' => '401000',
        'vente' => '701100',
        'achat' => '601100',
        'charge' => '607000',
        'banque' => '521000',
        'caisse' => '571000',
        'tva_collectee' => '443000',
        'tva_deductible' => '445000',
    ],

    'journaux' => [
        'vente_client' => 'VT',
        'achat_fournisseur' => 'HA',
        'banque' => 'BQ',
        'caisse' => 'CA',
        'od' => 'OD',
    ],
];
