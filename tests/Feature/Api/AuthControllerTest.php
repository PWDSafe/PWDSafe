<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = User::firstOrFail();
    }

    public function testLoginReturnsTokenAndVaultData(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'some@email.com',
            'password' => 'password',
            'device_name' => 'CLI on workstation',
        ])->assertOk();

        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'email', 'primarygroup', 'uses_login_hash', 'separate_vault_password'],
            'vault_data' => ['encrypted_privkey', 'salt', 'pubkey'],
        ]);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'CLI on workstation']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'some@email.com',
            'password' => 'wrongpassword',
            'device_name' => 'CLI on workstation',
        ])->assertStatus(401);
    }

    public function testLoginAcceptsNonEmailUsername(): void
    {
        User::registerUser('robin', 'password');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'robin',
            'password' => 'password',
            'device_name' => 'CLI on workstation',
        ])->assertOk();

        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'email', 'primarygroup', 'uses_login_hash', 'separate_vault_password'],
            'vault_data' => ['encrypted_privkey', 'salt', 'pubkey'],
        ]);
    }

    public function testLoginFailsForUnknownEmail(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
            'device_name' => 'CLI on workstation',
        ])->assertStatus(401);
    }

    public function testLoginRequiresTwoFactorCodeWhenEnabled(): void
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();
        $this->user->two_factor_secret = encrypt($secret);
        $this->user->save();

        $this->postJson('/api/auth/login', [
            'email' => 'some@email.com',
            'password' => 'password',
            'device_name' => 'CLI on workstation',
        ])->assertStatus(422)->assertJson(['needs_2fa' => true]);

        $validCode = $google2fa->getCurrentOtp($secret);

        $this->postJson('/api/auth/login', [
            'email' => 'some@email.com',
            'password' => 'password',
            'device_name' => 'CLI on workstation',
            'totp_code' => $validCode,
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function testTokenCanAccessProtectedApiRoute(): void
    {
        $token = $this->user->createToken('CLI')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/groups')
            ->assertOk();
    }

    public function testRequestWithoutTokenIsUnauthorized(): void
    {
        $this->getJson('/api/groups')->assertUnauthorized();
    }

    public function testLogoutRevokesCurrentToken(): void
    {
        $accessToken = $this->user->createToken('CLI');
        $token = $accessToken->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessToken->accessToken->id]);
    }

    public function testDevicesListAndRevoke(): void
    {
        $token = $this->user->createToken('CLI')->plainTextToken;
        $this->user->createToken('Browser extension');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/devices')
            ->assertOk();

        $response->assertJsonCount(2);
        $names = array_column($response->json(), 'name');
        $this->assertEqualsCanonicalizing(['CLI', 'Browser extension'], $names);

        $extensionTokenId = collect($response->json())->firstWhere('name', 'Browser extension')['id'];

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/auth/devices/' . $extensionTokenId)
            ->assertOk();

        $this->assertCount(1, $this->user->tokens);
    }
}
