<?php

namespace Tests\Feature;

use App\Credential;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ResetAccountTest extends TestCase
{
    use DatabaseMigrations;

    public function testViewResetWithLdapDisabled(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $this->get('/settings/resetaccount')->assertUnprocessable();
        $this->delete('/settings/resetaccount')->assertUnprocessable();
    }

    public function testViewResetWithLdapEnabledButWorkingPassword(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');
        config(['ldap.enabled' => true]);

        $this->get('/settings/resetaccount')->assertUnprocessable();
    }

    public function testViewResetWithLdapEnabledIncorrectPassword(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        // Simulate LDAP password change: vault_key derived from wrong password
        $this->setupVaultSessionForUser($user, 'password1');
        session()->put('password', 'password1'); // ResetAccountController uses this to derive the new vault_key
        config(['ldap.enabled' => true]);

        $this->get('/settings/resetaccount')->assertOk();
    }

    public function testViewResetWithLdapEnabledDoReset(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        // Simulate LDAP password change: vault_key derived from wrong password
        $this->setupVaultSessionForUser($user, 'password1');
        session()->put('password', 'password1'); // ResetAccountController uses this to derive the new vault_key
        config(['ldap.enabled' => true]);

        $this->get('/groups/' . $user->primarygroup)
            ->assertOk()
            ->assertDontSee('testsite');

        Credential::addCredentials([
            'creds' => 'testsite',
            'credu' => 'teuser',
            'credn' => 'Some note',
            'encrypted' => $this->encryptedPayloadForUsers('some password', $user),
            'currentgroupid' => $user->primarygroup,
        ]);

        $this->get('/groups/' . $user->primarygroup)
            ->assertOk()
            ->assertSee('testsite');

        $this->delete('/settings/resetaccount')->assertRedirect('/groups/' . $user->primarygroup);

        $this->get('/groups/' . $user->primarygroup)
            ->assertOk()
            ->assertDontSee('testsite');
    }
}
