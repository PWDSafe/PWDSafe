<?php

namespace Tests\Feature;

use App\Helpers\Encryption;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class VaultSetupTest extends TestCase
{
    use DatabaseMigrations;

    private function createUnconfiguredUser(): User
    {
        // Simulate an LDAP user who has been registered but vault not yet configured.
        User::registerUser('ldap@example.com', 'ldappassword');
        $user = User::first();
        $user->vault_configured = false;
        $user->uses_login_hash = false;
        $user->save();

        return $user->fresh();
    }

    public function testUnconfiguredUserIsRedirectedToSetup(): void
    {
        $user = $this->createUnconfiguredUser();
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->get('/groups')->assertRedirect(route('vault.setup'));
    }

    public function testSetupPageLoads(): void
    {
        $user = $this->createUnconfiguredUser();
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->get(route('vault.setup'))->assertOk()->assertSee('vault-setup');
    }

    public function testApiVaultSetupConfiguresVault(): void
    {
        $user = $this->createUnconfiguredUser();
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $enc = app(Encryption::class);
        [$privkey, $pubkey] = $enc->genNewKeys();
        $newVaultSalt = bin2hex(random_bytes(32));
        $newVaultKey = Encryption::deriveVaultKey('mysafepassword', $newVaultSalt);
        $encryptedPrivkey = $enc->encV2($privkey, $newVaultKey);

        $this->postJson('/api/vault/setup', [
            'encrypted_privkey' => $encryptedPrivkey,
            'vault_salt' => $newVaultSalt,
            'pubkey' => $pubkey,
        ])->assertOk()->assertJsonStructure(['redirect']);

        $user = $user->fresh();
        $this->assertTrue($user->isVaultConfigured());
        // No login_hash provided (LDAP-style setup) → separate vault password, login hash NOT set.
        $this->assertTrue($user->hasSeparateVaultPassword());
        $this->assertFalse((bool) $user->uses_login_hash);
        $this->assertEquals($encryptedPrivkey, $user->privkey);
        $this->assertEquals($newVaultSalt, $user->privkey_salt);
        $this->assertEquals(trim($pubkey), trim($user->pubkey));
        $this->assertTrue(session('vault_unlocked'));
    }

    public function testApiVaultSetupRequiresAuth(): void
    {
        $this->postJson('/api/vault/setup', [
            'encrypted_privkey' => 'abc',
            'vault_salt' => str_repeat('a', 64),
            'pubkey' => 'pem',
        ])->assertUnauthorized();
    }

    public function testApiVaultSetupValidatesInput(): void
    {
        $user = $this->createUnconfiguredUser();
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->postJson('/api/vault/setup', [])->assertUnprocessable();
        $this->postJson('/api/vault/setup', [
            'encrypted_privkey' => 'ok',
            'vault_salt' => 'tooshort',
            'pubkey' => 'ok',
        ])->assertUnprocessable();
    }

    public function testConfiguredUserIsNotRedirectedToSetup(): void
    {
        User::registerUser('local@example.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $this->get('/groups')->assertRedirect();
    }

    public function testVaultSetupUpdatesLoginHashWhenProvided(): void
    {
        $user = $this->createUnconfiguredUser();
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $enc = app(Encryption::class);
        [$privkey, $pubkey] = $enc->genNewKeys();
        $newVaultSalt = bin2hex(random_bytes(32));
        $newVaultKey = Encryption::deriveVaultKey('safepassword', $newVaultSalt);
        $encryptedPrivkey = $enc->encV2($privkey, $newVaultKey);
        $loginHash = Encryption::deriveLoginHash($newVaultKey, 'safepassword');

        $this->postJson('/api/vault/setup', [
            'encrypted_privkey' => $encryptedPrivkey,
            'vault_salt' => $newVaultSalt,
            'pubkey' => $pubkey,
            'login_hash' => $loginHash,
        ])->assertOk();

        $user = $user->fresh();
        $this->assertTrue($user->isVaultConfigured());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($loginHash, $user->password));
        // login_hash provided → same-password case → separate_vault_password must be false.
        $this->assertFalse($user->hasSeparateVaultPassword());
    }

    public function testVaultSetupWithoutLoginHashKeepsExistingPassword(): void
    {
        $user = $this->createUnconfiguredUser();
        $originalPassword = $user->password;
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $enc = app(Encryption::class);
        [$privkey, $pubkey] = $enc->genNewKeys();
        $newVaultSalt = bin2hex(random_bytes(32));
        $newVaultKey = Encryption::deriveVaultKey('safepassword', $newVaultSalt);
        $encryptedPrivkey = $enc->encV2($privkey, $newVaultKey);

        $this->postJson('/api/vault/setup', [
            'encrypted_privkey' => $encryptedPrivkey,
            'vault_salt' => $newVaultSalt,
            'pubkey' => $pubkey,
        ])->assertOk();

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function testAdminCreatedUserIsRedirectedToVaultSetup(): void
    {
        $user = User::createPendingLocalUser('admin-created@example.com', 'temppassword', 'Test User');
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->get('/groups')->assertRedirect(route('vault.setup'));
    }

    public function testVaultSetupPagePassesUpdateLoginHashFlagForPendingLocalUser(): void
    {
        $user = User::createPendingLocalUser('pending@example.com', 'temppassword');
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->get(route('vault.setup'))
            ->assertOk()
            ->assertSee('data-update-login-hash="1"', false);
    }

    public function testVaultSetupPageDoesNotPassUpdateLoginHashFlagForLdapUser(): void
    {
        $user = $this->createUnconfiguredUser();
        $user->auth_source = 'ldap';
        $user->save();
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->get(route('vault.setup'))
            ->assertOk()
            ->assertSee('data-update-login-hash=""', false);
    }

    public function testVaultSetupPagePassesUpdateLoginHashFlagForLegacyLocalUser(): void
    {
        // Legacy users restored from a pre-ZK database have auth_source = null (not 'local').
        // They must still get updateLoginHash = true so the vault password becomes their login credential.
        $user = $this->createUnconfiguredUser();
        // auth_source is null (as registerUser() leaves it) — do NOT set it to 'local'.
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->get(route('vault.setup'))
            ->assertOk()
            ->assertSee('data-update-login-hash="1"', false);
    }

    /**
     * Regression: after vault setup for a legacy local user the second login must succeed
     * with the safe password and fail with the original login password.
     * Before the fix, setup() always set separate_vault_password=true, which left the user
     * in a contradictory state (no login_salt) and caused a "wrong password" error if they
     * entered their original password on the next login.
     */
    public function testLegacyLocalUserCanLoginWithSafePasswordAfterVaultSetup(): void
    {
        // Create a legacy v1-style local user: no vault configured, no login hash.
        User::registerUser('legacy-local@example.com', 'original-password');
        $user = User::where('email', 'legacy-local@example.com')->first();
        $user->vault_configured = false;
        $user->uses_login_hash = false;
        $user->save();
        $user = $user->fresh();

        // First login: server migrates privkey and returns needs_vault_setup.
        $firstLogin = $this->postJson('/login', [
            'email' => 'legacy-local@example.com',
            'password' => 'original-password',
        ]);
        $firstLogin->assertOk()->assertJson(['needs_vault_setup' => true]);

        // Simulate vault setup: user sets 'safe-password' as the new safe (and login) password.
        $enc = app(Encryption::class);
        $user = $user->fresh();
        $newVaultSalt = bin2hex(random_bytes(32));
        $newVaultKey = Encryption::deriveVaultKey('safe-password', $newVaultSalt);
        [$privkey] = $enc->genNewKeys();
        $encryptedPrivkey = $enc->encV2($privkey, $newVaultKey);
        $loginHash = Encryption::deriveLoginHash($newVaultKey, 'safe-password');

        $this->postJson('/api/vault/setup', [
            'encrypted_privkey' => $encryptedPrivkey,
            'vault_salt' => $newVaultSalt,
            'pubkey' => 'dummy-pubkey',
            'login_hash' => $loginHash,
        ])->assertOk();

        $user = $user->fresh();
        $this->assertFalse($user->hasSeparateVaultPassword());
        $this->assertTrue($user->isVaultConfigured());

        // Log out so the next login attempt starts fresh.
        Auth::logout();

        // Second login with the safe password must succeed.
        // login.js sends the hex login_hash as the password field.
        $this->postJson('/login', [
            'email' => 'legacy-local@example.com',
            'password' => $loginHash,
        ])->assertOk();

        Auth::logout();

        // Login with the original password must fail — the stored hash is now derived from the safe password.
        $vaultKeyOld = Encryption::deriveVaultKey('original-password', $newVaultSalt);
        $loginHashOld = Encryption::deriveLoginHash($vaultKeyOld, 'original-password');
        $this->postJson('/login', [
            'email' => 'legacy-local@example.com',
            'password' => $loginHashOld,
        ])->assertUnprocessable();
    }

    /**
     * After the ZK migration all legacy users have vault_configured = false. On first login
     * a legacy local user with privkey_salt set (v2 format) will send a login_hash.
     * The server must NOT prematurely set vault_configured = true — it must route the user
     * to vault.setup (not vault.unlock) and set migration_vault_key_hex in the session.
     */
    public function testLegacyLocalUserWithSaltIsRoutedToVaultSetupNotUnlock(): void
    {
        // Simulate a fully-registered user whose vault_configured was cleared by the ZK migration.
        User::registerUser('legacy@example.com', 'password');
        $user = User::where('email', 'legacy@example.com')->first();
        $user->vault_configured = false;
        $user->uses_login_hash = false;
        $user->save();
        $user = $user->fresh();

        // Derive the login_hash the client would send (preflight returns non-null salt → client derives).
        $vaultKey = Encryption::deriveVaultKey('password', $user->privkey_salt);
        $loginHash = Encryption::deriveLoginHash($vaultKey, 'password');

        $response = $this->postJson('/login', [
            'email' => 'legacy@example.com',
            'password' => 'password',
            'login_hash' => bin2hex($loginHash),
        ]);

        $response->assertOk()
            ->assertJson(['needs_vault_setup' => true])
            ->assertJsonPath('redirect', route('vault.setup'));

        // vault_configured must still be false — only VaultController::setup() should set it.
        $this->assertFalse((bool) $user->fresh()->vault_configured);

        // migration_vault_key_hex must be set so VaultSetup.vue can re-encrypt the existing privkey.
        $this->assertNotNull(session('migration_vault_key_hex'));
    }
}
