<?php

namespace Tests\Feature;

use App\Helpers\LdapAuthentication;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use DatabaseMigrations;

    public function testRegisterUserAndChangePasswordViaWeb(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $this->get('/settings')
            ->assertOk()
            ->assertSee('Safe password');

        // Wrong old password with no crypto fields: should fail on auth check.
        $result = $this->from('/settings')
            ->post('/settings', [
                'change_type' => 'vault',
                'oldpwd' => 'something',
            ]);
        $result->assertRedirect('/settings')->assertSessionHasErrors('oldpwd');

        $user = \App\User::firstOrFail();
        $this->post("/groups/{$user->primarygroup}/add", [
            'name' => 'Site1',
            'user' => 'The username',
            'notes' => 'Some notes here',
            'encrypted' => $this->encryptedPayloadForUsers('The super secret password', $user),
        ]);

        $cred = \App\Encryptedcredential::firstOrFail();
        $olddata = $cred->data;
        $result = $this->post('/settings', array_merge(
            [
                'change_type' => 'vault',
                'oldpwd' => 'password',
            ],
            $this->encryptedPrivkeyPayload($user->fresh(), 'password', 'longpassword'),
        ));
        $result->assertRedirect('/settings')
            ->assertSessionDoesntHaveErrors();

        // RSA key pair is unchanged — encrypted credential data stays the same
        $this->assertEquals($olddata, $cred->fresh()->data);
    }

    public function testChangingPasswordWithIncorrectCurrentPassword(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $this->from('/settings')
            ->post('/settings', [
                'change_type' => 'vault',
                'oldpwd' => 'something',
            ])
            ->assertSessionHasErrors('oldpwd');
    }

    public function testViewingChangePasswordWithLdapEnabledShowsSafePasswordSection(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');
        config(['ldap.enabled' => true]);

        $this->get('/settings')
            ->assertOk()
            ->assertSee('Safe password');
    }

    public function testViewingChangePasswordWithLdapEnabledPasswordChangedInLdap(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        // Simulate LDAP password change: vault_key derived from wrong password
        $this->setupVaultSessionForUser($user, 'password1');
        config(['ldap.enabled' => true]);

        $this->mock(LdapAuthentication::class)
            ->shouldReceive('login')
            ->andReturnTrue();

        $this->from('/settings')
            ->post('/settings', [
                'change_type' => 'vault',
                'oldpwd' => 'password15',
                'password' => 'password1',
                'password_confirmation' => 'password1'
            ])->assertSessionHasErrors('oldpwd');

        $this->from('/settings')
            ->post('/settings', [
                'change_type' => 'vault',
                'oldpwd' => 'password',
                'password' => 'password1',
                'password_confirmation' => 'password1'
            ])->assertSessionDoesntHaveErrors();
    }

    public function testChangePasswordLdapEnabledPasswordChangedInLdap(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        // Simulate LDAP password change: vault_key derived from wrong password
        $this->setupVaultSessionForUser($user, 'password1');
        config(['ldap.enabled' => true]);

        $this->get('/settings/safe-password')
            ->assertOk()
            ->assertSee('We cannot decrypt your safe');
    }
}
