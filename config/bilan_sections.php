<?php

/**
 * Rubriques du bilan SYSCOHADA — Actif | Passif (capitaux propres inclus dans le passif).
 */
return [
    'actif' => [
        'actif_immobilise' => [
            'libelle' => 'ACTIF IMMOBILISÉ',
            'titre' => 'Immobilisations (classe 2)',
        ],
        'actif_circulant' => [
            'libelle' => 'ACTIF CIRCULANT',
            'titre' => 'Stocks et créances (classes 3–4)',
        ],
        'tresorerie_actif' => [
            'libelle' => 'TRÉSORERIE ACTIF',
            'titre' => 'Banques et caisses (classe 5)',
        ],
    ],
    'passif' => [
        'capital' => [
            'libelle' => 'CAPITAL',
            'titre' => 'Capital social',
        ],
        'reserves' => [
            'libelle' => 'RÉSERVES',
            'titre' => 'Réserves et primes',
        ],
        'report_resultat' => [
            'libelle' => 'REPORT & RÉSULTAT',
            'titre' => 'Report à nouveau et résultat de l\'exercice',
        ],
        'emprunts_associes' => [
            'libelle' => 'DETTES FINANCIÈRES',
            'titre' => 'Emprunts et dettes financières (classe 1)',
        ],
        'passif_circulant' => [
            'libelle' => 'DETTES D\'EXPLOITATION',
            'titre' => 'Dettes fournisseurs et passif circulant',
        ],
        'tresorerie_passif' => [
            'libelle' => 'TRÉSORERIE PASSIF',
            'titre' => 'Découverts bancaires',
        ],
    ],
];
