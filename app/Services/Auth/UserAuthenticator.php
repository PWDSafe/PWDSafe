<?php

namespace App\Services\Auth;

use App\Helpers\LdapAuthentication;
use App\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;

/**
 * Verifies login credentials and two-factor codes outside of the
 * session-based web login flow, for use by token-based API clients.
 */
class UserAuthenticator
{
    public function __construct(
        private readonly LdapAuthentication $ldap,
        private readonly Google2FA $google2fa,
    ) {
    }

    /**
     * Verify a user's login credentials (local password/login_hash, or LDAP bind).
     * Returns the user on success, or null if the credentials are invalid.
     *
     * LDAP users without a configured vault are rejected here — first-time LDAP
     * provisioning requires the web login/setup flow.
     */
    public function attempt(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return null;
        }

        if (config('ldap.enabled') && $user->auth_source === 'ldap') {
            if (!$user->isVaultConfigured() || !$this->ldap->login($email, $password)) {
                return null;
            }

            return $user;
        }

        if (Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }

    public function requiresTwoFactor(User $user): bool
    {
        return !is_null($user->two_factor_secret);
    }

    public function verifyTwoFactorCode(User $user, string $code): bool
    {
        return $this->google2fa->verify($code, decrypt($user->two_factor_secret));
    }
}
