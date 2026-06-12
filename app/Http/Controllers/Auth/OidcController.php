<?php

namespace App\Http\Controllers\Auth;

use App\AuditLog;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OidcController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $scopes = array_filter(explode(' ', config('services.oidc.scopes', 'openid email profile')));

        /** @var \Laravel\Socialite\Two\AbstractProvider $provider */
        $provider = Socialite::driver('oidc');

        return $provider->setScopes($scopes)->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $socialiteUser = Socialite::driver('oidc')->user();
        $email = $socialiteUser->getEmail();

        if (! $email) {
            return redirect('/login')->withErrors(['email' => 'The OIDC provider did not return an email address.']);
        }

        $user = User::where('email', $email)->first();
        $isNewUser = false;

        if (! $user) {
            try {
                User::registerUser($email, bin2hex(random_bytes(32)));
            } catch (UniqueConstraintViolationException) {
                $user = User::where('email', $email)->first();
            }
            $user = $user ?? User::where('email', $email)->firstOrFail();
            // Mark as not yet configured so the safe setup page handles fresh key generation.
            $user->vault_configured = false;
            $user->auth_source = 'oidc';
            $user->name = $socialiteUser->getName() ?: $email;
            $user->save();
            $isNewUser = true;
        } else {
            $dirty = false;
            if (is_null($user->auth_source)) {
                $user->auth_source = 'oidc';
                $dirty = true;
            }
            $oidcName = $socialiteUser->getName();
            if ($oidcName && $user->name !== $oidcName) {
                $user->name = $oidcName;
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }
        }

        // Returning users whose vault key was tied to their login password cannot derive
        // it via OIDC. Mark them as having a separate safe password so the middleware
        // routes them to the safe unlock page.
        if (! $isNewUser && $user->isVaultConfigured() && ! $user->hasSeparateVaultPassword()) {
            $user->separate_vault_password = true;
            $user->save();
        }

        Auth::loginUsingId($user->id);
        AuditLog::logLogin($user, $request);

        if (! $user->isVaultConfigured()) {
            return redirect()->route('vault.setup');
        }

        return redirect('/');
    }
}
