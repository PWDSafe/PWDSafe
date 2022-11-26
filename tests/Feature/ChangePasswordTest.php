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
        session()->put('password', 'password');

        $this->get('/changepwd')
            ->assertOk()
            ->assertSee('Old password');
        $result = $this->from('/changepwd')
            ->post('/changepwd', [
                'oldpwd' => 'something',
                'password' => 'short'
            ]);
        $result->assertRedirect('/changepwd')->assertSessionHasErrors();

        $result = $this->post('/changepwd', [
            'oldpwd' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short'
        ]);
        $result->assertRedirect('/changepwd')->assertSessionHasErrors();

        $user = \App\User::firstOrFail();
        $this->post("/groups/{$user->primarygroup}/add", [
            'site' => 'Site1',
            'user' => 'The username',
            'pass' => 'The super secret password',
            'notes' => 'Some notes here',
        ]);

        $cred = \App\Encryptedcredential::firstOrFail();
        $olddata = $cred->data;
        $result = $this->post('/changepwd', [
            'oldpwd' => 'password',
            'password' => 'longpassword',
            'password_confirmation' => 'longpassword'
        ]);
        $result->assertRedirect('/changepwd')
            ->assertSessionDoesntHaveErrors();

        $this->assertNotEquals($olddata, $cred->fresh()->data);
    }

    public function testChangingPasswordWithIncorrectCurrentPassword(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password');

        $this->from('/changepwd')
            ->post('/changepwd', [
                'oldpwd' => 'something',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword'
            ])
            ->assertSessionHasErrors('oldpwd');
    }

    public function testViewingChangePasswordWithLdapEnabledFeatureDisabled(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password');
        config(['ldap.enabled' => true]);

        $this->get('/changepwd')
            ->assertOk()
            ->assertSee('This feature is disabled');
    }

    public function testViewingChangePasswordWithLdapEnabledPasswordChangedInLdap(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password1');
        config(['ldap.enabled' => true]);

        /** @phpstan-ignore-next-line */
        $this->mock(LdapAuthentication::class)
            ->shouldReceive('login')
            ->andReturnTrue();

        $this->from('/changepwd')
            ->post('/changepwd', [
                'oldpwd' => 'password15',
                'password' => 'password1',
                'password_confirmation' => 'password1'
            ])->assertSessionHasErrors('oldpwd');

        $this->from('/changepwd')
            ->post('/changepwd', [
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
        session()->put('password', 'password1');
        config(['ldap.enabled' => true]);

        $this->get('/changepwd')
            ->assertOk()
            ->assertSee('cannot seem to decrypt your private key');
    }
}
