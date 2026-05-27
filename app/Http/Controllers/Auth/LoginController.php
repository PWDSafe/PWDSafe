<?php

namespace App\Http\Controllers\Auth;

use App\AuditLog;
use App\Helpers\LdapAuthentication;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request, Google2FA $google2fa): JsonResponse|Redirector|RedirectResponse|Application|Response
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            // If user has two fa enabled, logout and redirect to verify
            if (!is_null(auth()->user()->two_factor_secret)) {
                $user = auth()->user();
                $vaultData = ['encrypted_privkey' => $user->privkey, 'salt' => $user->privkey_salt];
                session()->put('username', $user->email);
                // For users who unlock their vault client-side (LDAP Case 3, local Case 2),
                // vault_key and vault_unlocked aren't set at login time — mark unlock as pending
                // so VerifyOtpController can distinguish a valid session from an expired one.
                if (!session()->has('vault_key') && !session()->has('vault_unlocked')) {
                    session()->put('vault_unlock_pending', true);
                }
                auth()->logout();

                if ($request->expectsJson()) {
                    return response()->json([
                        'needs_2fa' => true,
                        'redirect' => url('verifyotp'),
                        'vault_data' => $vaultData,
                    ]);
                }

                return redirect('verifyotp');
            }

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            // Users who unlock their vault client-side (separate vault password, or LDAP with
            // configured vault) skip the server-side privkey check — the server never holds
            // their vault key. Only redirect to changepassword for users where the server
            // should be able to decrypt the privkey but can't (e.g. LDAP password change).
            $needsClientSideUnlock = auth()->user()->hasSeparateVaultPassword()
                || (config('ldap.enabled') && auth()->user()->isVaultConfigured())
                || !auth()->user()->isVaultConfigured();

            if (!$needsClientSideUnlock && !auth()->user()->canDecryptPrivkey()) {
                if ($request->expectsJson()) {
                    return response()->json(['redirect' => route('settings')]);
                }

                return redirect()->route('settings');
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    protected function attemptLogin(Request $request): bool
    {
        $credentials = $request->only('email', 'password');
        $forceInternal = $request->boolean('internal');

        if (($forceInternal || !config('ldap.enabled')) && Auth::attempt($credentials)) {
            $user = auth()->user();

            if ($user->uses_login_hash) {
                if ($user->hasSeparateVaultPassword()) {
                    // Case 2: local user with separate vault password — auth done, vault unlock needed.
                    // vault_unlocked is NOT set here; the client will POST to /api/vault/confirm-unlock.
                    return true;
                }

                // Case 1: local user, same password — vault is client-side, nothing to do server-side.
                session()->put('vault_unlocked', true);
            } else {
                // v1 user: set up server-side vault_key for the session.
                $user->setupVaultSession($credentials['password']);
                // Migrate to login_hash format if the client sent the derived hash.
                if ($request->filled('login_hash')) {
                    $user->password = Hash::make($request->input('login_hash'));
                    $user->uses_login_hash = true;
                    // vault_configured is intentionally NOT set here; only VaultController::setup()
                    // should mark the vault as configured once the client has actually set it up.
                    $user->save();
                }
                // Pass the derived vault key to VaultSetupController for re-encrypting the existing
                // privkey client-side (mirrors the LDAP unconfigured-user path).
                if (!$user->isVaultConfigured()) {
                    session()->put('migration_vault_key_hex', session('vault_key'));
                }
                session()->put('vault_unlocked', true);
            }

            return true;
        } elseif (!$forceInternal && config('ldap.enabled') && app(LdapAuthentication::class)->login($credentials['email'], $credentials['password'])) {
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                // Brand-new LDAP user: create without a vault (vault_configured stays false).
                User::registerUser($credentials['email'], $credentials['password']);
                $user = User::where('email', $credentials['email'])->first();
                // Mark as not yet configured so middleware redirects to /vault/setup.
                $user->vault_configured = false;
                $user->auth_source = 'ldap';
                $user->save();
            } else {
                if (is_null($user->auth_source)) {
                    $user->auth_source = 'ldap';
                }
                $user->password = Hash::make($credentials['password']);
                $user->save();
            }

            Auth::loginUsingId($user->id);

            if (!$user->isVaultConfigured()) {
                // Case 4: LDAP user without a vault — set up a migration key so the setup page
                // can re-encrypt the existing (server-generated) private key client-side.
                session()->put('password', $credentials['password']);
                $user->setupVaultSession($credentials['password']);
                // Store the derived vault_key_hex in the session so VaultSetupController can pass
                // it to the client for client-side re-encryption.
                session()->put('migration_vault_key_hex', session('vault_key'));
                return true;
            }

            // Case 3: LDAP user with an already-configured vault — auth done, vault unlock needed.
            // Do NOT call setupVaultSession; the client derives the vault key from the safe password.
            return true;
        }

        return false;
    }

    /**
     * Return JSON with vault key data when the login request is AJAX,
     * so the client can derive the vault key and cache the private key locally.
     */
    protected function sendLoginResponse(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        AuditLog::logLogin(auth()->user(), $request);

        if ($request->expectsJson()) {
            $user = auth()->user();

            if (!$user->isVaultConfigured()) {
                // Case 4: LDAP user without a vault — redirect to setup page.
                return response()->json([
                    'needs_vault_setup' => true,
                    'redirect' => route('vault.setup'),
                    'vault_data' => [
                        'encrypted_privkey' => $user->privkey,
                        'salt' => $user->privkey_salt,
                        'pubkey' => $user->pubkey,
                    ],
                    'vault_key_hex' => session('migration_vault_key_hex'),
                ]);
            }

            if ($user->hasSeparateVaultPassword()) {
                // Cases 2 & 3: vault unlock required before vault access.
                return response()->json([
                    'needs_vault_unlock' => true,
                    'redirect' => route('vault.unlock'),
                    'vault_data' => [
                        'encrypted_privkey' => $user->privkey,
                        'salt' => $user->privkey_salt,
                        'pubkey' => $user->pubkey,
                    ],
                ]);
            }

            // Case 1: local user, same password — vault already unlocked.
            return response()->json([
                'redirect' => route('group', $user->primarygroup),
                'vault_data' => [
                    'encrypted_privkey' => $user->privkey,
                    'salt' => $user->privkey_salt,
                    'pubkey' => $user->pubkey,
                ],
            ]);
        }

        return $this->authenticated($request, $this->guard()->user());
    }

    protected function authenticated(Request $request, User $user): RedirectResponse
    {
        return redirect()->route('group', $user->primarygroup);
    }
}
