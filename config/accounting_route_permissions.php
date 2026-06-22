<?php

/**
 * Permissions par route (Spatie). Les préfixes sont testés en premier, puis les routes exactes.
 * Les routes « write » écrasent le préfixe lecture du même module.
 */
return [
    'prefixes' => [
        'accounting.saisie.' => 'saisie.view',
        'accounting.livres.' => 'livres.view',
        'accounting.etats.' => 'etats.view',
        'accounting.fiscalite.' => 'fiscalite.view',
        'accounting.exercices.' => 'exercices.view',
        'accounting.parametres.' => 'parametres.view',
        'accounting.facturation.' => 'facturation.view',
        'accounting.taches.' => 'taches.view',
        'accounting.analytique.' => 'analytique.view',
    ],

    'routes' => [
        'dashboard' => 'dashboard.view',
        'dashboard.data' => 'dashboard.view',

        'accounting.modules.show' => null, // résolu dans le contrôleur selon le module

        'accounting.saisie.ecritures.store' => 'saisie.create',
        'accounting.saisie.ecritures.validate' => 'saisie.validate',
        'accounting.saisie.ecritures.unvalidate' => 'saisie.unvalidate',
        'accounting.saisie.ecritures.delete' => 'saisie.delete',
        'accounting.saisie.import-releve.store' => 'saisie.create',

        'accounting.exercices.cloturer' => 'exercices.process',
        'accounting.exercices.pre-cloture' => 'exercices.process',
        'accounting.exercices.ouverture.creer' => 'exercices.create',
        'accounting.exercices.ouverture.bilan' => 'exercices.create',
        'accounting.exercices.report-a-nouveau.generer' => 'exercices.process',
        'accounting.exercices.exercice.save' => 'exercices.update',
        'accounting.exercices.exercice.courant' => 'exercices.update',

        'accounting.parametres.plan-comptable.save' => 'parametres.update',
        'accounting.parametres.journaux.save' => 'parametres.update',
        'accounting.parametres.taux-change.save' => 'parametres.update',
        'accounting.parametres.tiers.save' => 'parametres.update',
        'accounting.parametres.societe.save' => 'parametres.update',
        'accounting.parametres.societe.logo' => 'parametres.update',
        'accounting.parametres.exercice.save' => 'parametres.update',
        'accounting.parametres.exercice.courant' => 'parametres.update',

        'accounting.fiscalite.declarations.generer' => 'fiscalite.process',
        'accounting.fiscalite.declarations.deposer' => 'fiscalite.process',

        'accounting.etats.export' => 'etats.export',

        'accounting.facturation.factures.save' => 'facturation.create',
        'accounting.facturation.factures.valider' => 'facturation.validate',
        'accounting.facturation.factures.annuler' => 'facturation.delete',
        'accounting.facturation.paiements.store' => 'facturation.process',
        'accounting.facturation.demandes.save' => 'facturation.create',
        'accounting.facturation.demandes.traiter' => 'facturation.validate',
        'accounting.facturation.workflow.save' => 'facturation.update',
        'accounting.facturation.produits.save' => 'facturation.update',

        'accounting.taches.save' => 'taches.create',
        'accounting.taches.etapes.toggle' => 'taches.process',
        'accounting.taches.rapport' => 'taches.process',
        'accounting.taches.fichier' => 'taches.process',

        'accounting.analytique.axes.save' => 'analytique.create',
        'accounting.analytique.sections.save' => 'analytique.create',
        'accounting.analytique.config.save' => 'analytique.update',
        'accounting.analytique.compte-axes.save' => 'analytique.update',
    ],

    /** module URL key => permission vue */
    'modules' => [
        'dashboard' => 'dashboard.view',
        'saisie' => 'saisie.view',
        'livres' => 'livres.view',
        'etats' => 'etats.view',
        'analytique' => 'analytique.view',
        'fiscalite' => 'fiscalite.view',
        'exercices' => 'exercices.view',
        'parametres' => 'parametres.view',
        'facturation' => 'facturation.view',
        'taches' => 'taches.view',
    ],
];
