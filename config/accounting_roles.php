<?php

/**
 * Rôles métiers SYSCOHADA — libellés et rôles système protégés.
 * Les permissions par rôle sont définies dans AccountingRolesPermissionsSeeder.
 */
return [
    'labels' => [
        'super_admin' => 'Super Administrateur',
        'admin_comptable' => 'Administrateur Comptable',
        'comptable' => 'Comptable',
        'caissier' => 'Caissier',
        'tresorier' => 'Trésorier / Banque',
        'auditeur' => 'Auditeur / Contrôleur',
        'direction' => 'Direction / DG',
    ],

    'descriptions' => [
        'super_admin' => 'Accès total — utilisateurs, paramètres, clôture, suppression.',
        'admin_comptable' => 'Responsable comptabilité — saisie, validation, états, journaux.',
        'comptable' => 'Saisie et consultation — pas de clôture ni suppression après validation.',
        'caissier' => 'Journal caisse et consultation caisse uniquement.',
        'tresorier' => 'Journal banque, rapprochement et trésorerie.',
        'auditeur' => 'Lecture seule sur tous les livres et états.',
        'direction' => 'Dashboard et états financiers en lecture.',
    ],

    /** Rôles non supprimables / permissions verrouillées dans l'UI */
    'protected' => ['super_admin'],

    /** Rôles recevant toutes les permissions (comme super_admin) */
    'full_access' => ['super_admin'],

    'default_admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@millenium-erp.local'),
        'password' => env('ADMIN_PASSWORD', 'Admin@2025'),
        'name' => 'Super Administrateur',
        'role' => 'super_admin',
    ],
];
