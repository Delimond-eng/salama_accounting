<?php

/**
 * Permissions comptables — format Spatie : {entity}.{action}
 * Synchronisées par AccountingRolesPermissionsSeeder et DatabaseSeeder.
 */
return [

    'dashboard' => [
        'entity' => 'dashboard',
        'label' => 'Tableau de bord',
        'actions' => ['view'],
    ],

    'saisie' => [
        'entity' => 'saisie',
        'label' => 'Saisie comptable',
        'actions' => ['view', 'create', 'update', 'validate', 'delete'],
    ],

    'livres' => [
        'entity' => 'livres',
        'label' => 'Livres comptables',
        'actions' => ['view', 'export'],
    ],

    'tresorerie' => [
        'entity' => 'tresorerie',
        'label' => 'Trésorerie (caisse & banque)',
        'actions' => ['view', 'create', 'update', 'export'],
    ],

    'etats' => [
        'entity' => 'etats',
        'label' => 'États financiers',
        'actions' => ['view', 'export'],
    ],

    'exercices' => [
        'entity' => 'exercices',
        'label' => 'Exercices comptables',
        'actions' => ['view', 'create', 'update', 'process'],
    ],

    'parametres' => [
        'entity' => 'parametres',
        'label' => 'Paramètres & référentiel',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'fiscalite' => [
        'entity' => 'fiscalite',
        'label' => 'Fiscalité',
        'actions' => ['view', 'export', 'process'],
    ],

    'users' => [
        'entity' => 'users',
        'label' => 'Utilisateurs',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'roles' => [
        'entity' => 'roles',
        'label' => 'Rôles & permissions',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'audit' => [
        'entity' => 'audit',
        'label' => 'Journal d\'audit',
        'actions' => ['view'],
    ],

    'facturation' => [
        'entity' => 'facturation',
        'label' => 'Facturation & trésorerie',
        'actions' => ['view', 'create', 'update', 'validate', 'delete', 'export', 'process'],
    ],
];
