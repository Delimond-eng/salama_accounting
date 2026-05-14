<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    'dashboard_admin' => [
        'entity' => 'dashboard_admin',
        'label'  => 'Tableau de bord (vue globale)',
        'actions' => ['view'],
    ],

    /*
    |--------------------------------------------------------------------------
    | COMPTABILITÉ
    |--------------------------------------------------------------------------
    */
    'accounting_journal' => [
        'entity' => 'accounting_journal',
        'label'  => 'Journal comptable',
        'actions' => ['view', 'create', 'update', 'delete', 'export', 'validate'],
    ],

    'accounting_ledger' => [
        'entity' => 'accounting_ledger',
        'label'  => 'Grand livre',
        'actions' => ['view', 'export'],
    ],

    'accounting_trial_balance' => [
        'entity' => 'accounting_trial_balance',
        'label'  => 'Balance générale',
        'actions' => ['view', 'export'],
    ],

    'accounting_subsidiary_balance' => [
        'entity' => 'accounting_subsidiary_balance',
        'label'  => 'Balance auxiliaire',
        'actions' => ['view', 'export'],
    ],

    'accounting_cash_draft' => [
        'entity' => 'accounting_cash_draft',
        'label'  => 'Brouillard de caisse',
        'actions' => ['view', 'create', 'update', 'delete', 'validate'],
    ],

    'accounting_reconciliation' => [
        'entity' => 'accounting_reconciliation',
        'label'  => 'Lettrage des comptes',
        'actions' => ['view', 'process'],
    ],

    'accounting_closing' => [
        'entity' => 'accounting_closing',
        'label'  => 'Clôture comptable',
        'actions' => ['view', 'process'],
    ],

    'accounting_reopening' => [
        'entity' => 'accounting_reopening',
        'label'  => 'Réouverture d\'exercice',
        'actions' => ['view', 'process'],
    ],

    'accounting_exports' => [
        'entity' => 'accounting_exports',
        'label'  => 'Exports comptables',
        'actions' => ['view', 'export'],
    ],

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATION
    |--------------------------------------------------------------------------
    */
    'users' => [
        'entity' => 'users',
        'label'  => 'Utilisateurs',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'roles' => [
        'entity' => 'roles',
        'label'  => 'Roles & permissions',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'logs' => [
        'entity' => 'logs',
        'label'  => 'Journal accès',
        'actions' => ['view'],
    ],
];
