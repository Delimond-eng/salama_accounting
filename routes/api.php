<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\Api\BiometricApiController;
use Illuminate\Support\Facades\Route;

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

});
