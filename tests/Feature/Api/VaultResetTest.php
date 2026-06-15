<?php

namespace Tests\Feature\Api;

use App\Credential;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class VaultResetTest extends TestCase
{
    use DatabaseMigrations;

    public function testGuestCannotReset(): void
    {
        $this->postJson('/api/vault/reset')->assertUnauthorized();
    }

    public function testResetWipesVaultAndCredentials(): void
    {
        $user = $this->createAndLoginUser();
        $user = $user->fresh();

        Credential::create(['groupid' => $user->primarygroup, 'name' => 'example.com', 'url' => 'example.com', 'username' => 'u', 'notes' => '']);

        $this->assertNotNull($user->privkey);
        $this->assertNotNull($user->privkey_salt);
        $this->assertTrue($user->isVaultConfigured());
        $this->assertDatabaseCount('credentials', 1);

        $response = $this->postJson('/api/vault/reset');

        $response->assertOk()->assertJsonStructure(['redirect']);

        $user->refresh();

        $this->assertNull($user->privkey);
        $this->assertNull($user->privkey_salt);
        $this->assertNull($user->pubkey);
        $this->assertFalse($user->isVaultConfigured());
        $this->assertFalse($user->hasSeparateVaultPassword());
        $this->assertDatabaseCount('credentials', 0);
    }

    public function testResetRedirectsToVaultSetup(): void
    {
        $this->createAndLoginUser();

        $response = $this->postJson('/api/vault/reset');

        $response->assertOk()->assertJson(['redirect' => route('vault.setup')]);
    }

    public function testResetIsAccessibleBeforeVaultIsUnlocked(): void
    {
        $user = User::factory()->create([
            'separate_vault_password' => true,
            'vault_configured' => true,
        ]);
        $this->setupVaultSessionForUser($user, 'testing123');
        $this->actingAs($user);

        // Simulate a user whose vault_unlocked session is not set
        session()->forget('vault_unlocked');
        session()->forget('vault_key');

        // JSON request should pass through the middleware even without vault_unlocked
        $response = $this->postJson('/api/vault/reset');

        $response->assertOk();
    }
}
