<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class RegisterAndLoginTest extends TestCase
{
    use DatabaseMigrations;

    public function testRedirectToLogin(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function testRegisterAndLogin(): void
    {
        $this->registerUser();
        $this->assertDatabaseHas('users', ['email' => 'some@email.com']);

        $result = $this->from('/login')->post('/login', ['email' => 'some@email.com', 'password' => 'somethingThatIsWrong']);
        $result->assertSessionHasErrors();
        $result->assertRedirect('/login');

        $result = $this->from('/login')->post('/login', ['email' => 'some@email.com', 'password' => 'password']);
        $result->assertSessionDoesntHaveErrors();
        $result->assertRedirect('/groups/' . \App\User::firstOrFail()->primarygroup);
    }

    public function testRegisterAUserThatAlreadyExists(): void
    {
        $this->registerUser();
        $this->assertDatabaseHas('users', ['email' => 'some@email.com']);

        $result = $this->post('/register', $this->registrationPayload('some@email.com', 'password'));
        $result->assertSessionHasErrors('email');
        $this->assertCount(1, \App\User::all());
    }

    public function testLogout(): void
    {
        $this->registerUser();
        $this->loginUser();
        $this->assertAuthenticated();
        $this->post('/logout');
        $this->assertGuest();
    }

    public function testRedirectedToPrimaryGroup(): void
    {
        $this->registerUser();
        $this->loginUser();
        $user = \App\User::firstOrFail();
        $this->get('/')->assertRedirect('/groups/' . $user->primarygroup);
    }

    public function testRegisterLeavesUserAuthenticated(): void
    {
        // After registration the vault_unlocked flag is set, so the user stays logged in.
        $this->post('/register', $this->registrationPayload('some@email.com', 'password'));
        $this->assertAuthenticated();
        $this->get('/')->assertRedirect(); // Redirects to primary group
    }

    public function testAjaxLoginReturnsVaultData(): void
    {
        $this->registerUser();

        $this->postJson('/login', ['email' => 'some@email.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure([
                'redirect',
                'vault_data' => ['encrypted_privkey', 'salt', 'pubkey'],
            ])
            ->assertJsonMissing(['needs_2fa']);
    }

    public function testAjaxLoginFailsWithWrongPassword(): void
    {
        $this->registerUser();

        $this->postJson('/login', ['email' => 'some@email.com', 'password' => 'wrongpassword'])
            ->assertStatus(422);
    }

    public function testAjaxLoginWith2FaReturnsNeedsOtp(): void
    {
        $google2fa = new Google2FA();
        User::factory()->create(['two_factor_secret' => encrypt($google2fa->generateSecretKey())]);
        $user = User::first();

        $this->postJson('/login', ['email' => $user->email, 'password' => 'testing123'])
            ->assertOk()
            ->assertJson(['needs_2fa' => true])
            ->assertJsonStructure([
                'needs_2fa',
                'redirect',
                'vault_data' => ['encrypted_privkey', 'salt'],
            ]);

        $this->assertGuest();
    }
}
