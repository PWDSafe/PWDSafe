<?php

use App\Http\Controllers\CredentialsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PasswordForController;
use App\Http\Controllers\PreLogonFirstPageCallback;
use App\Http\Controllers\SecurityCheckController;
use App\Http\Controllers\TwofaSettingsController;
use App\Http\Controllers\VerifyOtpController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\GroupChangeNameController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupDeleteController;
use App\Http\Controllers\ManageGroupMembersController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\HealthController;

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

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', [PreLogonFirstPageCallback::class, 'index']);
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groupCreate');
    Route::post('/groups/create', [GroupController::class, 'store']);
    Route::get('/groups/{group}', [GroupController::class, 'index'])->name('group');
    Route::post('/groups/{group}/export', [ExportController::class, 'store'])->name('export');
    Route::delete('/groups/{group}', [GroupDeleteController::class, 'delete']);
    Route::get('/groups/{group}/add', [GroupController::class, 'addCredential'])->name('addCredentials');
    Route::post('/groups/{group}/add', [GroupController::class, 'storeCredential']);
    Route::get('/groups/{group}/members', [ManageGroupMembersController::class, 'index'])->name('groupManageMembers');
    Route::post('/groups/{group}/members', [ManageGroupMembersController::class, 'store']);
    Route::delete('/groups/{group}/members', [ManageGroupMembersController::class, 'destroy']);
    Route::patch('/groups/{group}/members/{user}', [ManageGroupMembersController::class, 'update']);
    Route::get('/groups/{group}/delete', [GroupDeleteController::class, 'index']);
    Route::get('/groups/{group}/name', [GroupChangeNameController::class, 'index']);
    Route::post('/groups/{group}/name', [GroupChangeNameController::class,'store']);
    Route::get('/pwdfor/{credential}', [PasswordForController::class, 'index']);
    Route::post('/search', [SearchController::class, 'store'])->name('search');
    Route::get('/search/{search}', [SearchController::class, 'index']);
    Route::get('/changepwd', [ChangePasswordController::class, 'index'])->name('changepassword');
    Route::post('/changepwd', [ChangePasswordController::class, 'store']);

    Route::get('/settings/twofa', [TwofaSettingsController::class, 'index'])->name('settings.twofa');
    Route::post('/settings/twofa', [TwofaSettingsController::class, 'store']);
    Route::delete('/settings/twofa', [TwofaSettingsController::class, 'destroy']);

    Route::post('/cred/{credential}', [CredentialsController::class, 'update']);
    Route::get('/credential/{credential}', [CredentialsController::class, 'index'])->name('credential');
    Route::delete('/credential/{credential}', [CredentialsController::class, 'delete']);
    Route::put('/credential/{credential}', [CredentialsController::class, 'update']);
    Route::get('/securitycheck', [SecurityCheckController::class, 'index'])->name('securitycheck');

    Route::post('/import', [ImportController::class, 'store']);
});

Route::get('/verifyotp', [VerifyOtpController::class, 'index'])->name('verifyotp');
Route::post('/verifyotp', [VerifyOtpController::class, 'store']);

Route::get('/health', [HealthController::class, 'index']);

Auth::routes([
    'reset' => false,
    'verify' => false,
    'confirm' => false,
    'register' => !config('ldap.enabled')
]);
