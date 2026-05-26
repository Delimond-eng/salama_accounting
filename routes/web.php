<?php

use App\Http\Controllers\AccountingModuleController;
use App\Http\Controllers\ComptableExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ParametresController;
use App\Http\Controllers\EtatsController;
use App\Http\Controllers\ExercicesController;
use App\Http\Controllers\FacturationController;
use App\Http\Controllers\FiscaliteController;
use App\Http\Controllers\LivresController;
use App\Http\Controllers\SaisieController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Millenium ERP
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::middleware(['auth', 'accounting.permission'])->group(function () {
    // Dashboard
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [HomeController::class, 'data'])->name('dashboard.data');

    // Accounting Routes
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/modules/{module}', [AccountingModuleController::class, 'show'])
            ->name('modules.show');
        Route::get('/a-venir/{slug}', [AccountingModuleController::class, 'placeholder'])
            ->name('placeholder');

        Route::get('/journal', fn () => redirect()->route('accounting.livres.journal'))->name('journal');
        Route::get('/cash-draft', fn () => redirect()->route('accounting.saisie.caisse'))->name('cash-draft');
        Route::get('/ledger', fn () => redirect()->route('accounting.livres.grand-livre'))->name('ledger');
        Route::get('/trial-balance', fn () => redirect()->route('accounting.livres.balance'))->name('trial-balance');
        Route::get('/subsidiary-balance', fn () => redirect()->route('accounting.livres.auxiliaire'))->name('subsidiary-balance');

        Route::prefix('saisie')->name('saisie.')->group(function () {
            foreach (['nouvelle', 'achats', 'ventes', 'banque', 'caisse', 'od', 'devises'] as $page) {
                Route::get("/{$page}", fn (SaisieController $c) => $c->liste($page))->name($page);
                Route::get("/{$page}/ecriture", fn (SaisieController $c) => $c->ecriture($page))->name("{$page}.ecriture");
                Route::get("/{$page}/ecriture/{id}", fn (SaisieController $c, int $id) => $c->ecriture($page, $id))
                    ->whereNumber('id')->name("{$page}.ecriture.edit");
            }
            Route::get('/import-releve', [SaisieController::class, 'importReleve'])->name('import-releve');

            Route::get('/metadata', [SaisieController::class, 'metadata'])->name('metadata');
            Route::get('/ecritures', [SaisieController::class, 'ecrituresList'])->name('ecritures.list');
            Route::get('/ecritures/{id}', [SaisieController::class, 'ecritureShow'])->whereNumber('id')->name('ecritures.show');
            Route::post('/ecritures/store', [SaisieController::class, 'store'])->name('ecritures.store');
            Route::post('/ecritures/{id}/validate', [SaisieController::class, 'validateEcriture'])->whereNumber('id')->name('ecritures.validate');
            Route::post('/ecritures/{id}/delete', [SaisieController::class, 'destroy'])->whereNumber('id')->name('ecritures.delete');
            Route::get('/comptes/search', [SaisieController::class, 'comptesSearch'])->name('comptes.search');
            Route::get('/tiers/search', [SaisieController::class, 'tiersSearch'])->name('tiers.search');
            Route::get('/taux', [SaisieController::class, 'tauxDevise'])->name('taux');
            Route::post('/import-releve', [SaisieController::class, 'importReleveStore'])->name('import-releve.store');
        });
        Route::get('/reconciliation', fn () => redirect()->route('accounting.livres.lettrage'))->name('reconciliation');

        Route::prefix('livres')->name('livres.')->group(function () {
            Route::get('/journal', [LivresController::class, 'journalGeneral'])->name('journal');
            Route::get('/grand-livre', [LivresController::class, 'grandLivre'])->name('grand-livre');
            Route::get('/balance', [LivresController::class, 'balanceGenerale'])->name('balance');
            Route::get('/auxiliaire', [LivresController::class, 'balanceAuxiliaire'])->name('auxiliaire');
            Route::get('/lettrage', [LivresController::class, 'lettrage'])->name('lettrage');
            Route::get('/comptes-tiers', [LivresController::class, 'comptesTiers'])->name('comptes-tiers');
            Route::get('/banque', [LivresController::class, 'livreBanque'])->name('banque');
            Route::get('/caisse', [LivresController::class, 'livreCaisse'])->name('caisse');

            Route::get('/metadata', [LivresController::class, 'metadata'])->name('metadata');
            Route::get('/tresorerie/comptes', [LivresController::class, 'apiComptesTresorerie'])->name('tresorerie.comptes');
            Route::get('/tresorerie/data', [LivresController::class, 'apiLivreTresorerie'])->name('tresorerie.data');
            Route::post('/preferences', [LivresController::class, 'savePreferences'])->name('preferences');
            Route::get('/balance/data', [LivresController::class, 'apiBalance'])->name('balance.data');
            Route::get('/journal/data', [LivresController::class, 'apiJournal'])->name('journal.data');
            Route::get('/grand-livre/data', [LivresController::class, 'apiGrandLivre'])->name('grand-livre.data');
            Route::get('/grand-livre/general/data', [LivresController::class, 'apiGrandLivreGeneral'])->name('grand-livre.general.data');
            Route::get('/auxiliaire/data', [LivresController::class, 'apiAuxiliaire'])->name('auxiliaire.data');
            Route::get('/lettrage/data', [LivresController::class, 'apiLettrage'])->name('lettrage.data');
            Route::get('/comptes-tiers/data', [LivresController::class, 'apiComptesTiers'])->name('comptes-tiers.data');
        });
        Route::redirect('/closing', '/accounting/exercices/cloture')->name('closing');
        Route::redirect('/reopening', '/accounting/exercices/ouverture')->name('reopening');

        Route::prefix('exercices')->name('exercices.')->group(function () {
            Route::get('/', [ExercicesController::class, 'index'])->name('index');
            Route::get('/ouverture', [ExercicesController::class, 'ouverture'])->name('ouverture');
            Route::get('/cloture', [ExercicesController::class, 'cloture'])->name('cloture');
            Route::get('/report-a-nouveau', [ExercicesController::class, 'reportANouveau'])->name('report-a-nouveau');

            Route::get('/metadata', [ExercicesController::class, 'metadata'])->name('metadata');
            Route::get('/liste', [ExercicesController::class, 'liste'])->name('liste');
            Route::get('/controles', [ExercicesController::class, 'controles'])->name('controles');
            Route::get('/controles-mensuels', [ExercicesController::class, 'controlesMensuels'])->name('controles-mensuels');
            Route::post('/pre-cloture', [ExercicesController::class, 'preCloture'])->name('pre-cloture');
            Route::post('/cloturer', [ExercicesController::class, 'cloturer'])->name('cloturer');
            Route::post('/exercice/save', [ExercicesController::class, 'saveExercice'])->name('exercice.save');
            Route::post('/exercice/courant', [ExercicesController::class, 'definirCourant'])->name('exercice.courant');
            Route::post('/ouverture/creer', [ExercicesController::class, 'creerSuivant'])->name('ouverture.creer');
            Route::post('/ouverture/bilan', [ExercicesController::class, 'genererBilanOuverture'])->name('ouverture.bilan');
            Route::post('/report-a-nouveau/generer', [ExercicesController::class, 'genererReportANouveau'])->name('report-a-nouveau.generer');
        });
        Route::get('/exports', fn () => redirect()->route('accounting.etats.exports'))->name('exports');

        Route::prefix('etats')->name('etats.')->group(function () {
            Route::get('/bilan', [EtatsController::class, 'bilan'])->name('bilan');
            Route::get('/compte-resultat', [EtatsController::class, 'compteResultat'])->name('compte-resultat');
            Route::get('/flux-tresorerie', [EtatsController::class, 'fluxTresorerie'])->name('flux-tresorerie');
            Route::get('/variation-kp', [EtatsController::class, 'variationKp'])->name('variation-kp');
            Route::get('/annexes', [EtatsController::class, 'annexes'])->name('annexes');
            Route::get('/comparatif', [EtatsController::class, 'comparatif'])->name('comparatif');
            Route::get('/exports', [EtatsController::class, 'exports'])->name('exports');

            Route::get('/metadata', [EtatsController::class, 'metadata'])->name('metadata');
            Route::get('/bilan/data', [EtatsController::class, 'apiBilan'])->name('bilan.data');
            Route::get('/compte-resultat/data', [EtatsController::class, 'apiCompteResultat'])->name('compte-resultat.data');
            Route::get('/flux-tresorerie/data', [EtatsController::class, 'apiFluxTresorerie'])->name('flux-tresorerie.data');
            Route::get('/variation-kp/data', [EtatsController::class, 'apiVariationKp'])->name('variation-kp.data');
            Route::get('/annexes/data', [EtatsController::class, 'apiAnnexes'])->name('annexes.data');
            Route::get('/comparatif/data', [EtatsController::class, 'apiComparatif'])->name('comparatif.data');
            Route::get('/export/{type}', [EtatsController::class, 'exportCsv'])->name('export');
        });

        Route::prefix('facturation')->name('facturation.')->group(function () {
            Route::get('/', [FacturationController::class, 'index'])->name('index');
            Route::get('/clients', [FacturationController::class, 'facturesClients'])->name('clients');
            Route::get('/fournisseurs', [FacturationController::class, 'facturesFournisseurs'])->name('fournisseurs');
            Route::get('/clients/nouvelle', [FacturationController::class, 'factureForm'])->defaults('type', 'clients')->name('clients.create');
            Route::get('/fournisseurs/nouvelle', [FacturationController::class, 'factureForm'])->defaults('type', 'fournisseurs')->name('fournisseurs.create');
            Route::get('/clients/{id}', [FacturationController::class, 'factureForm'])->whereNumber('id')->defaults('type', 'clients')->name('clients.edit');
            Route::get('/fournisseurs/{id}', [FacturationController::class, 'factureForm'])->whereNumber('id')->defaults('type', 'fournisseurs')->name('fournisseurs.edit');
            Route::get('/avoirs-clients', [FacturationController::class, 'avoirsClients'])->name('avoirs-clients');
            Route::get('/avoirs-fournisseurs', [FacturationController::class, 'avoirsFournisseurs'])->name('avoirs-fournisseurs');
            Route::get('/produits', [FacturationController::class, 'produits'])->name('produits');
            Route::get('/paiements', [FacturationController::class, 'paiements'])->name('paiements');
            Route::get('/echeancier-clients', [FacturationController::class, 'echeancierClients'])->name('echeancier-clients');
            Route::get('/echeancier-fournisseurs', [FacturationController::class, 'echeancierFournisseurs'])->name('echeancier-fournisseurs');
            Route::get('/demandes', [FacturationController::class, 'demandesFonds'])->name('demandes');
            Route::get('/demandes/nouvelle', [FacturationController::class, 'demandeForm'])->name('demandes.create');
            Route::get('/demandes/{id}', [FacturationController::class, 'demandeForm'])->whereNumber('id')->name('demandes.show');
            Route::get('/workflow', [FacturationController::class, 'workflow'])->name('workflow');
            Route::get('/stock', [\App\Http\Controllers\StockController::class, 'inventaire'])->name('stock');
            Route::get('/stock/bons-commande', [\App\Http\Controllers\StockController::class, 'bonsCommande'])->name('stock.bons-commande');
            Route::get('/stock/mouvements', [\App\Http\Controllers\StockController::class, 'mouvements'])->name('stock.mouvements');

            Route::get('/metadata', [FacturationController::class, 'metadata'])->name('metadata');
            Route::get('/comptes-tresorerie', [FacturationController::class, 'apiComptesTresorerie'])->name('comptes-tresorerie');
            Route::get('/factures', [FacturationController::class, 'apiFactures'])->name('factures.list');
            Route::get('/factures/{id}', [FacturationController::class, 'apiFactureShow'])->whereNumber('id')->name('factures.show');
            Route::post('/factures/save', [FacturationController::class, 'apiFactureSave'])->name('factures.save');
            Route::post('/factures/{id}/valider', [FacturationController::class, 'apiFactureValider'])->whereNumber('id')->name('factures.valider');
            Route::post('/factures/{id}/annuler', [FacturationController::class, 'apiFactureAnnuler'])->whereNumber('id')->name('factures.annuler');
            Route::get('/factures/{id}/pdf', [FacturationController::class, 'pdfFacture'])->whereNumber('id')->name('factures.pdf');
            Route::get('/tiers', [FacturationController::class, 'apiTiers'])->name('tiers');
            Route::get('/produits/list', [FacturationController::class, 'apiProduits'])->name('produits.list');
            Route::post('/produits/save', [FacturationController::class, 'apiProduitSave'])->name('produits.save');
            Route::post('/paiements/facture/{id}', [FacturationController::class, 'apiPayerFacture'])->whereNumber('id')->name('paiements.store');
            Route::get('/paiements/list', [FacturationController::class, 'apiPaiements'])->name('paiements.list');
            Route::get('/paiements/{id}/pdf', [FacturationController::class, 'pdfRecu'])->whereNumber('id')->name('paiements.pdf');
            Route::get('/echeancier', [FacturationController::class, 'apiEcheancier'])->name('echeancier');
            Route::get('/demandes/list', [FacturationController::class, 'apiDemandes'])->name('demandes.list');
            Route::get('/demandes/{id}/detail', [FacturationController::class, 'apiDemandeShow'])->whereNumber('id')->name('demandes.detail');
            Route::post('/demandes/save', [FacturationController::class, 'apiDemandeSave'])->name('demandes.save');
            Route::post('/demandes/{id}/traiter', [FacturationController::class, 'apiDemandeTraiter'])->whereNumber('id')->name('demandes.traiter');
            Route::get('/workflow/list', [FacturationController::class, 'apiWorkflows'])->name('workflow.list');
            Route::post('/workflow/save', [FacturationController::class, 'apiWorkflowSave'])->name('workflow.save');

            Route::get('/stock/metadata', [\App\Http\Controllers\StockController::class, 'apiMetadata'])->name('stock.metadata');
            Route::get('/stock/inventaire', [\App\Http\Controllers\StockController::class, 'apiInventaire'])->name('stock.inventaire');
            Route::get('/stock/mouvements/list', [\App\Http\Controllers\StockController::class, 'apiMouvements'])->name('stock.mouvements.list');
            Route::post('/stock/mouvement', [\App\Http\Controllers\StockController::class, 'apiMouvementManuel'])->name('stock.mouvement');
            Route::get('/stock/mouvements/{id}/pdf', [\App\Http\Controllers\StockController::class, 'pdfMouvement'])->whereNumber('id')->name('stock.mouvement.pdf');
            Route::get('/stock/bons-commande/list', [\App\Http\Controllers\StockController::class, 'apiBonsCommande'])->name('stock.bons-commande.list');
            Route::post('/stock/bons-commande/save', [\App\Http\Controllers\StockController::class, 'apiBonCommandeSave'])->name('stock.bons-commande.save');
        });

        Route::prefix('fiscalite')->name('fiscalite.')->group(function () {
            Route::get('/tva-collectee', [FiscaliteController::class, 'tvaCollectee'])->name('tva-collectee');
            Route::get('/tva-deductible', [FiscaliteController::class, 'tvaDeductible'])->name('tva-deductible');
            Route::get('/dsf', [FiscaliteController::class, 'dsf'])->name('dsf');
            Route::get('/is', [FiscaliteController::class, 'impotSocietes'])->name('is');
            Route::get('/declarations', [FiscaliteController::class, 'declarations'])->name('declarations');
            Route::get('/echeances', [FiscaliteController::class, 'echeances'])->name('echeances');

            Route::get('/metadata', [FiscaliteController::class, 'metadata'])->name('metadata');
            Route::get('/tva-collectee/data', [FiscaliteController::class, 'apiTvaCollectee'])->name('tva-collectee.data');
            Route::get('/tva-deductible/data', [FiscaliteController::class, 'apiTvaDeductible'])->name('tva-deductible.data');
            Route::get('/dsf/data', [FiscaliteController::class, 'apiDsf'])->name('dsf.data');
            Route::get('/is/data', [FiscaliteController::class, 'apiIs'])->name('is.data');
            Route::get('/echeances/data', [FiscaliteController::class, 'apiEcheances'])->name('echeances.data');
            Route::get('/declarations/list', [FiscaliteController::class, 'apiDeclarationsList'])->name('declarations.list');
            Route::post('/declarations/generer', [FiscaliteController::class, 'apiGenererDeclarations'])->name('declarations.generer');
            Route::post('/declarations/deposer', [FiscaliteController::class, 'apiMarquerDeposee'])->name('declarations.deposer');
        });

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/livres/{type}/{format}', [ComptableExportController::class, 'livres'])
                ->whereIn('format', ['pdf', 'excel', 'xlsx', 'csv'])->name('livres');
            Route::get('/etats/{type}/{format}', [ComptableExportController::class, 'etats'])
                ->whereIn('format', ['pdf', 'excel', 'xlsx', 'csv'])->name('etats');
            Route::get('/fiscalite/{type}/{format}', [ComptableExportController::class, 'fiscalite'])
                ->whereIn('format', ['pdf', 'excel', 'xlsx', 'csv'])->name('fiscalite');
            Route::get('/saisie/{format}', [ComptableExportController::class, 'saisie'])
                ->whereIn('format', ['pdf', 'excel', 'xlsx', 'csv'])->name('saisie');
            Route::get('/parametres/{type}/{format}', [ComptableExportController::class, 'parametres'])
                ->whereIn('format', ['pdf', 'excel', 'xlsx', 'csv'])->name('parametres');
            Route::get('/admin/{type}/{format}', [ComptableExportController::class, 'admin'])
                ->whereIn('format', ['pdf', 'excel', 'xlsx', 'csv'])->name('admin');
        });

        Route::prefix('parametres')->name('parametres.')->group(function () {
            Route::get('/plan-comptable', [ParametresController::class, 'planComptable'])->name('plan-comptable');
            Route::get('/journaux', [ParametresController::class, 'journaux'])->name('journaux');
            Route::get('/devises', [ParametresController::class, 'devises'])->name('devises');
            Route::get('/tiers', [ParametresController::class, 'tiers'])->name('tiers');
            Route::get('/societe', [ParametresController::class, 'societe'])->name('societe');

            Route::get('/context', [ParametresController::class, 'context'])->name('context');
            Route::post('/societe/select', [ParametresController::class, 'selectSociete'])->name('societe.select');

            Route::get('/plan-comptable/all', [ParametresController::class, 'planComptableAll'])->name('plan-comptable.all');
            Route::post('/plan-comptable/save', [ParametresController::class, 'planComptableSave'])->name('plan-comptable.save');

            Route::get('/journaux/all', [ParametresController::class, 'journauxAll'])->name('journaux.all');
            Route::post('/journaux/save', [ParametresController::class, 'journauxSave'])->name('journaux.save');

            Route::get('/devises/all', [ParametresController::class, 'devisesAll'])->name('devises.all');
            Route::post('/taux-change/save', [ParametresController::class, 'tauxChangeSave'])->name('taux-change.save');

            Route::get('/tiers/all', [ParametresController::class, 'tiersAll'])->name('tiers.all');
            Route::post('/tiers/save', [ParametresController::class, 'tiersSave'])->name('tiers.save');

            Route::get('/societe/detail', [ParametresController::class, 'societeDetail'])->name('societe.detail');
            Route::post('/societe/save', [ParametresController::class, 'societeSave'])->name('societe.save');
            Route::post('/societe/logo', [ParametresController::class, 'societeLogo'])->name('societe.logo');
            Route::post('/exercice/save', [ParametresController::class, 'exerciceSave'])->name('exercice.save');
            Route::post('/exercice/courant', [ParametresController::class, 'exerciceCourant'])->name('exercice.courant');
        });
    });

    // Admin pages
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', fn () => view('users'))
            ->name('users')
            ->middleware('can:users.view');
        Route::get('/roles', fn () => view('roles'))
            ->name('roles')
            ->middleware('can:roles.view');
        Route::get('/logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
            ->name('logs')
            ->middleware('can:audit.view');
        Route::get('/audit/logs', [\App\Http\Controllers\AuditLogController::class, 'list'])
            ->name('audit.logs')
            ->middleware('can:audit.view');
    });

    // Users/Roles management APIs (Vue)
    Route::get('/actions', [UserController::class, 'getActions'])
        ->name('actions')
        ->middleware('can:roles.view');
    Route::post('/role/create', [UserController::class, 'createOrUpdateRole'])
        ->name('role.create')
        ->middleware('canany:roles.create,roles.update');
    Route::get('/roles/all', [UserController::class, 'getAllRoles'])
        ->name('roles.all')
        ->middleware('can:roles.view');
    Route::post('/user/create', [UserController::class, 'createOrUpdateUser'])
        ->name('user.create')
        ->middleware('canany:users.create,users.update');
    Route::get('/users/all', [UserController::class, 'getAllUsers'])
        ->name('users.all')
        ->middleware('can:users.view');
    Route::post('/user/access', [UserController::class, 'attributeAccess'])
        ->name('user.access')
        ->middleware('can:users.update');
});
