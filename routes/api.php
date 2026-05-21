<?php

use App\Http\Controllers\ApiChangePasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded in bootstrap/app.php with the "api" middleware group.
*/

Route::post('/pwdchg', [ApiChangePasswordController::class, 'store']);
