<?php

namespace Tests;

use App\Helpers\Encryption;
use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Create the standard test user directly (bypasses the registration form so tests
     * do not need to supply client-computed crypto fields).
     * Creates a v1 user — tests that need v2 format can call setupVaultSessionForUser().
     */
    protected function registerUser(): void
    {
        User::registerUser('some@email.com', 'password');
    }

    /**
     * Build the full payload for a POST /register request, simulating what register.js
     * computes in the browser. Use this in tests that specifically exercise the form endpoint.
     *
     * @return array<string, string>
     */
    protected function registrationPayload(string $email, string $password): array
    {
        $enc = app(Encryption::class);
        [$privKey, $pubKey] = $enc->genNewKeys();
        $salt = bin2hex(random_bytes(32));
        $vaultKey = Encryption::deriveVaultKey($password, $salt);
        $loginHash = Encryption::deriveLoginHash($vaultKey, $password);

        return [
            'email' => $email,
            'password' => $loginHash,
            'password_confirmation' => $loginHash,
            'encrypted_privkey' => $enc->encV2($privKey, $vaultKey),
            'privkey_salt' => $salt,
            'pubkey' => $pubKey,
        ];
    }

    protected function loginUser(): \Illuminate\Testing\TestResponse
    {
        return $this->from('/login')
            ->post('/login', [
            'email' => 'some@email.com',
            'password' => 'password'
        ]);
    }

    /**
     * Create a factory user and set up a valid vault session for them.
     * The factory user starts in legacy format; this method migrates them to v2 inline.
     */
    protected function createAndLoginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->setupVaultSessionForUser($user, 'testing123');

        return $user->fresh();
    }

    /**
     * Build an 'encrypted' array for use in storeCredential / addCredentials requests.
     * Uses RSA-PKCS1-v1.5 (v1 format) — the server just stores whatever string the client sends.
     *
     * @return array<int, array{userid: int, data: string}>
     */
    protected function encryptedPayloadForUsers(string $plaintext, User ...$users): array
    {
        $encryption = app(Encryption::class);

        return array_values(array_map(
            fn (User $u) => ['userid' => $u->id, 'data' => $encryption->encWithPub($plaintext, $u->pubkey)],
            $users
        ));
    }

    /**
     * Build the client-side payload for a vault (safe) password change request (Section B, first separation).
     * Decrypts the user's current private key and re-encrypts it with a new vault key.
     * Also derives the new independent login hash so the server can set separate_vault_password = true.
     *
     * Simulates what changepwd.js does in the browser for Flow A (first separation).
     *
     * @return array{password: string, password_confirmation: string, new_encrypted_privkey: string, new_salt: string, new_login_salt: string}
     */
    protected function encryptedPrivkeyPayload(User $user, string $oldPassword, string $newPassword): array
    {
        $enc = app(Encryption::class);
        $oldVaultKey = Encryption::deriveVaultKey($oldPassword, $user->privkey_salt);
        $privkey = $enc->decV2($user->privkey, $oldVaultKey);
        $newSalt = bin2hex(random_bytes(32));
        $newVaultKey = Encryption::deriveVaultKey($newPassword, $newSalt);
        $newLoginSalt = bin2hex(random_bytes(32));
        // Login password remains the old password until the user explicitly changes it via Section A.
        $newLoginHash = Encryption::deriveLoginHashIndependent($oldPassword, $newLoginSalt);

        return [
            'password' => $newLoginHash,
            'password_confirmation' => $newLoginHash,
            'new_encrypted_privkey' => $enc->encV2($privkey, $newVaultKey),
            'new_salt' => $newSalt,
            'new_login_salt' => $newLoginSalt,
        ];
    }

    /**
     * Migrate a user to the v2 privkey format (if needed) and set session('vault_key').
     * With VAULT_PBKDF2_ITERATIONS=1 in phpunit.xml this is near-instantaneous.
     */
    protected function setupVaultSessionForUser(User $user, string $password): void
    {
        $enc = app(Encryption::class);

        if (!$user->isV2Format()) {
            $privkey = $enc->dec($user->privkey, $password);
            $salt = bin2hex(random_bytes(32));
            $vaultKey = Encryption::deriveVaultKey($password, $salt);
            $user->privkey = $enc->encV2($privkey, $vaultKey);
            $user->privkey_salt = $salt;
            $user->save();
        }

        $vaultKey = Encryption::deriveVaultKey($password, $user->fresh()->privkey_salt);
        session()->put('vault_key', bin2hex($vaultKey));
        // Also set vault_unlocked so v2-format users pass the auth middleware check.
        session()->put('vault_unlocked', true);
    }
}
