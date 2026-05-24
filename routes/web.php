<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded in bootstrap/app.php with the "web" middleware group.
*/

use App\Http\Controllers\CredentialsController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PasswordForController;
use App\Http\Controllers\PreLogonFirstPageCallback;
use App\Http\Controllers\ResetAccountController;
use App\Http\Controllers\SecurityCheckController;
use App\Http\Controllers\SharedCredentialController;
use App\Http\Controllers\TwofaSettingsController;
use App\Http\Controllers\Api\ExportController as ApiExportController;
use App\Http\Controllers\Api\GroupMembersController as ApiGroupMembersController;
use App\Http\Controllers\Api\SecurityCheckController as ApiSecurityCheckController;
use App\Http\Controllers\Api\VaultController;
use App\Http\Controllers\VaultSetupController;
use App\Http\Controllers\VaultUnlockController;
use App\Http\Controllers\VerifyOtpController;
use App\Http\Controllers\WarningMessageController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\GroupChangeNameController;
use App\Http\Controllers\Admin\AuthSettingsController;
use App\Http\Controllers\Admin\GeneralSettingsController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Auth\OidcController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupDeleteController;
use App\Http\Controllers\ManageGroupMembersController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('api/vault')->group(function () {
        Route::get('key-data', [VaultController::class, 'keyData']);
        Route::post('recover', [VaultController::class, 'recover']);
        Route::post('setup', [VaultController::class, 'setup']);
        Route::post('confirm-unlock', [VaultController::class, 'confirmUnlock']);
        Route::post('reset', [VaultController::class, 'reset']);
    });

    Route::get('/vault/setup', [VaultSetupController::class, 'show'])->name('vault.setup');
    Route::get('/vault/unlock', [VaultUnlockController::class, 'show'])->name('vault.unlock');

    Route::get('/api/groups/{group}/pubkeys', [GroupController::class, 'pubkeys']);
    Route::get('/api/groups/{group}/export-data', [ApiExportController::class, 'show']);
    Route::post('/api/groups/{group}/members/prepare', [ApiGroupMembersController::class, 'prepare']);
    Route::post('/api/groups/{group}/members/confirm', [ApiGroupMembersController::class, 'confirm']);
    Route::get('/api/securitycheck', [ApiSecurityCheckController::class, 'index']);
    Route::get('/api/users/search', [UserSearchController::class, 'index']);

    Route::get('/', [PreLogonFirstPageCallback::class, 'index']);
    Route::get('/groups', [GroupController::class, 'index'])->name('groups');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groupCreate');
    Route::post('/groups/create', [GroupController::class, 'store']);
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('group');
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
    Route::get('/search', function () {
        return redirect()->back();
    });
    Route::post('/search', [SearchController::class, 'store'])->name('search');
    Route::get('/search/{search}', [SearchController::class, 'index']);
    Route::get('/settings', [ProfileSettingsController::class, 'index'])->name('settings');
    Route::get('/settings/login-password', [ProfileSettingsController::class, 'loginPassword'])->name('settings.login');
    Route::get('/settings/safe-password', [ProfileSettingsController::class, 'safePassword'])->name('settings.vault');
    Route::post('/settings', [ProfileSettingsController::class, 'store']);

    Route::get('/settings/twofa', [TwofaSettingsController::class, 'index'])->name('settings.twofa');
    Route::post('/settings/twofa', [TwofaSettingsController::class, 'store']);
    Route::delete('/settings/twofa', [TwofaSettingsController::class, 'destroy']);

    Route::post('/settings/warningmessage', [WarningMessageController::class, 'store']);

    Route::get('/settings/resetaccount', [ResetAccountController::class, 'index']);
    Route::delete('/settings/resetaccount', [ResetAccountController::class, 'destroy']);

    Route::post('/cred/{credential}', [CredentialsController::class, 'update']);
    Route::get('/credential/{credential}', [CredentialsController::class, 'index'])->name('credential');
    Route::delete('/credential/{credential}', [CredentialsController::class, 'delete']);
    Route::put('/credential/{credential}', [CredentialsController::class, 'update']);
    Route::post('/credential/{credential}/share', [SharedCredentialController::class, 'store']);
    Route::get('/securitycheck', [SecurityCheckController::class, 'index'])->name('securitycheck');

    Route::post('/import', [ImportController::class, 'store']);

    Route::get('/shared', [SharedCredentialController::class, 'index']);
    Route::delete('/shared/{credential}', [SharedCredentialController::class, 'destroy']);
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.settings.auth'));
    Route::get('/settings/auth', [AuthSettingsController::class, 'index'])->name('admin.settings.auth');
    Route::post('/settings/auth', [AuthSettingsController::class, 'update']);
    Route::get('/settings/general', [GeneralSettingsController::class, 'index'])->name('admin.settings.general');
    Route::post('/settings/general', [GeneralSettingsController::class, 'update']);
    Route::get('/users', [AdminUsersController::class, 'index'])->name('admin.users');
    Route::post('/users', [AdminUsersController::class, 'store'])->name('admin.users.store');
    Route::post('/users/{user}/reset-password', [AdminUsersController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::patch('/users/{user}/name', [AdminUsersController::class, 'updateName'])->name('admin.users.update-name');
    Route::delete('/users/{user}', [AdminUsersController::class, 'destroy'])->name('admin.users.destroy');
});

Route::get('/api/vault/preflight', [VaultController::class, 'preflight']);

Route::get('/verifyotp', [VerifyOtpController::class, 'index'])->name('verifyotp');
Route::post('/verifyotp', [VerifyOtpController::class, 'store']);

Route::get('/health', [HealthController::class, 'index']);

Route::middleware('guest')->group(function () {
    Route::get('/auth/oidc/redirect', [OidcController::class, 'redirect'])->name('oidc.redirect');
    Route::get('/auth/oidc/callback', [OidcController::class, 'callback'])->name('oidc.callback');
});

Route::get('/shared/{credential}', [SharedCredentialController::class, 'show'])->name('shared.show');
Route::post('/shared/{credential}', [SharedCredentialController::class, 'show']);

Auth::routes([
    'reset' => false,
    'verify' => false,
    'confirm' => false,
    'register' => !config('ldap.enabled') && config('app.registration_enabled', true),
]);
