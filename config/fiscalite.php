<?php

return [
    'taux_tva_normal' => 18,
    'taux_tva_reduit' => 9,
    'taux_is' => 30,
    'prefixes_tva_collectee' => ['443'],
    'prefixes_tva_deductible' => ['445'],
    'compte_is' => '891',
    'echeances' => [
        ['type' => 'tva_mensuelle', 'libelle' => 'Déclaration TVA mensuelle', 'jour_limite' => 15, 'recurrence' => 'monthly'],
        ['type' => 'tva_trimestrielle', 'libelle' => 'Déclaration TVA trimestrielle', 'jour_limite' => 15, 'recurrence' => 'quarterly'],
        ['type' => 'is', 'libelle' => 'Impôt sur les sociétés', 'jour_limite' => 30, 'recurrence' => 'annual', 'mois' => 4],
        ['type' => 'dsf', 'libelle' => 'DSF annuelle', 'jour_limite' => 30, 'recurrence' => 'annual', 'mois' => 5],
        ['type' => 'cnps_mensuel', 'libelle' => 'CNSS / CNPS', 'jour_limite' => 10, 'recurrence' => 'monthly'],
    ],
    'formulaires' => [
        'tva_mensuelle' => 'Formulaire TVA — déclaration mensuelle',
        'tva_trimestrielle' => 'Formulaire TVA — déclaration trimestrielle',
        'is' => 'Formulaire IS — liquidation annuelle',
        'dsf' => 'Déclaration Statistique et Fiscale (DSF)',
    ],
];
