<?php

use App\Http\Controllers\TwofaSettingsController;
use App\Http\Controllers\VerifyOtpController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\GroupChangeNameController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupDeleteController;
use App\Http\Controllers\GroupShareController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => 'auth'], function() {
    Route::get('/', ['uses' => 'PreLogonFirstPageCallback@index']);
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groupCreate');
    Route::post('/groups/create', ['uses' => 'GroupController@store']);
    Route::get('/groups/{group}', ['uses' => 'GroupController@index'])->name('group');
    Route::delete('/groups/{group}', ['uses' => 'GroupDeleteController@delete']);
    Route::get('/groups/{group}/add', [GroupController::class, 'addCredential'])->name('addCredentials');
    Route::post('/groups/{group}/add', [GroupController::class, 'storeCredential']);
    Route::get('/groups/{group}/share', [GroupShareController::class, 'index'])->name('groupShare');
    Route::post('/groups/{group}/share', [GroupShareController::class, 'store']);
    Route::delete('/groups/{group}/share', [GroupShareController::class, 'destroy']);
    Route::get('/groups/{group}/delete', [GroupDeleteController::class, 'index']);
    Route::get('/groups/{group}/name', [GroupChangeNameController::class, 'index']);
    Route::post('/groups/{group}/name', [GroupChangeNameController::class,'store']);
    Route::get('/pwdfor/{credential}', ['uses' => 'PasswordForController@index']);
    Route::post('/search', [SearchController::class, 'store'])->name('search');
    Route::get('/search/{search}', [SearchController::class, 'index']);
    Route::get('/changepwd', [ChangePasswordController::class, 'index'])->name('changepassword');
    Route::post('/changepwd', [ChangePasswordController::class, 'store']);

    Route::get('/settings/twofa', [TwofaSettingsController::class, 'index'])->name('settings.twofa');
    Route::post('/settings/twofa', [TwofaSettingsController::class, 'store']);
    Route::delete('/settings/twofa', [TwofaSettingsController::class, 'destroy']);

    Route::post('/cred/{credential}', ['uses' => 'CredentialsController@update']);
    Route::get('/credential/{credential}', ['uses' => 'CredentialsController@index'])->name('credential');
    Route::delete('/credential/{credential}', ['uses' => 'CredentialsController@delete']);
    Route::put('/credential/{credential}', ['uses' => 'CredentialsController@update']);
    Route::get('/securitycheck', ['uses' => 'SecurityCheckController@index'])->name('securitycheck');

    Route::post('/import', ['uses' => 'ImportController@store']);
});

Route::get('/verifyotp', [VerifyOtpController::class, 'index'])->name('verifyotp');
Route::post('/verifyotp', [VerifyOtpController::class, 'store']);

Auth::routes([
    'reset' => false,
    'verify' => false,
    'confirm' => false,
    'register' => !config('ldap.enabled')
]);
