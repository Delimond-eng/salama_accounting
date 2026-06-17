<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\Api\BiometricApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Minimal API surface for mobile:
| - Scan station QR -> station data
| - Agent punch (check-in / check-out / confirmation) using matricule as unique id
|
*/

Route::middleware(["cors"])->group(function () {
    // Route de test pour le backup vers Google Drive
    Route::get('/backup', function () {
        try {
            // Appelle la commande backup:send
            Artisan::call('backup:send');

            return response()->json([
                'status' => 'success',
                'message' => 'La commande de sauvegarde a été exécutée.',
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'exécution du backup : ' . $e->getMessage()
            ], 500);
        }
    });
});
