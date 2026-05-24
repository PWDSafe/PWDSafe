<?php

namespace Tests\Feature;

use App\Group;
use App\Helpers\Encryption;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VaultUnlockTest extends TestCase
{
    use DatabaseMigrations;

    private function createSeparatePasswordUser(string $loginPassword, string $safePassword): User
    {
        $enc = app(Encryption::class);
        [$privkey, $pubkey] = $enc->genNewKeys();

        $vaultSalt = bin2hex(random_bytes(32));
        $vaultKey = Encryption::deriveVaultKey($safePassword, $vaultSalt);
        $encryptedPrivkey = $enc->encV2($privkey, $vaultKey);

        $loginSalt = bin2hex(random_bytes(32));
        $loginHash = Encryption::deriveLoginHashIndependent($loginPassword, $loginSalt);

        $group = new Group();
        $group->name = 'testuser@example.com';
        $group->save();

        $user = new User();
        $user->email = 'testuser@example.com';
        $user->password = Hash::make($loginHash);
        $user->pubkey = $pubkey;
        $user->privkey = $encryptedPrivkey;
        $user->privkey_salt = $vaultSalt;
        $user->login_salt = $loginSalt;
        $user->uses_login_hash = true;
        $user->vault_configured = true;
        $user->separate_vault_password = true;
        $user->primarygroup = $group->id;
        $user->save();
        $user->groups()->attach($group);

        return $user->fresh();
    }

    public function testSeparatePasswordUserWithoutVaultUnlockedIsRedirected(): void
    {
        $user = $this->createSeparatePasswordUser('loginpwd', 'safepwd');
        Auth::loginUsingId($user->id);
        // vault_unlocked NOT set in session

        $this->get('/groups')->assertRedirect(route('vault.unlock'));
    }

    public function testUnlockPageLoads(): void
    {
        $user = $this->createSeparatePasswordUser('loginpwd', 'safepwd');
        Auth::loginUsingId($user->id);

        $this->get(route('vault.unlock'))->assertOk()->assertSee('vault-unlock');
    }

    public function testConfirmUnlockSetsVaultUnlocked(): void
    {
        $user = $this->createSeparatePasswordUser('loginpwd', 'safepwd');
        Auth::loginUsingId($user->id);

        $this->assertFalse(session()->has('vault_unlocked'));

        $this->postJson('/api/vault/confirm-unlock', [])
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $this->assertTrue(session('vault_unlocked'));
    }

    public function testConfirmUnlockRedirectsToPrimaryGroup(): void
    {
        $user = $this->createSeparatePasswordUser('loginpwd', 'safepwd');
        Auth::loginUsingId($user->id);

        $response = $this->postJson('/api/vault/confirm-unlock', []);
        $response->assertOk();
        $this->assertStringContainsString('/groups/', $response->json('redirect'));
    }

    public function testConfirmUnlockRequiresAuth(): void
    {
        $this->postJson('/api/vault/confirm-unlock', [])->assertUnauthorized();
    }

    public function testUserWithVaultUnlockedCanAccessApp(): void
    {
        $user = $this->createSeparatePasswordUser('loginpwd', 'safepwd');
        Auth::loginUsingId($user->id);
        session()->put('vault_unlocked', true);

        $this->get('/groups')->assertOk();
    }
}
