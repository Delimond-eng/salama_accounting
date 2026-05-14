<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - SALAMA ACCOUNTING
|--------------------------------------------------------------------------
*/

Auth::routes();

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');

    // Accounting Routes
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/journal', fn () => view('accounting.journal'))->name('journal');
        Route::get('/ledger', fn () => view('accounting.ledger'))->name('ledger');
        Route::get('/trial-balance', fn () => view('accounting.trial-balance'))->name('trial-balance');
        Route::get('/subsidiary-balance', fn () => view('accounting.subsidiary-balance'))->name('subsidiary-balance');
        Route::get('/cash-draft', fn () => view('accounting.cash-draft'))->name('cash-draft');
        Route::get('/reconciliation', fn () => view('accounting.reconciliation'))->name('reconciliation');
        Route::get('/closing', fn () => view('accounting.closing'))->name('closing');
        Route::get('/reopening', fn () => view('accounting.reopening'))->name('reopening');
        Route::get('/exports', fn () => view('accounting.exports'))->name('exports');
    });

    // Admin pages
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', fn () => view('users', ['sites' => \App\Models\Station::all()]))
            ->name('users')
            ->middleware('can:users.view');
        Route::get('/roles', fn () => view('roles'))
            ->name('roles')
            ->middleware('can:roles.view');
        Route::get('/logs', fn () => view('logs'))
            ->name('logs')
            ->middleware('can:logs.view');
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
