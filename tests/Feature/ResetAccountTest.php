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
        session()->put('password', 'password');

        $this->get('/settings/resetaccount')->assertUnprocessable();
        $this->delete('/settings/resetaccount')->assertUnprocessable();
    }

    public function testViewResetWithLdapEnabledButWorkingPassword(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password');
        config(['ldap.enabled' => true]);

        $this->get('/settings/resetaccount')->assertUnprocessable();
    }

    public function testViewResetWithLdapEnabledIncorrectPassword(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password1');
        config(['ldap.enabled' => true]);

        $this->get('/settings/resetaccount')->assertOk();
    }

    public function testViewResetWithLdapEnabledDoReset(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password1');
        config(['ldap.enabled' => true]);

        $this->get('/groups/' . $user->primarygroup)
            ->assertOk()
            ->assertSee('No credentials!');

        Credential::addCredentials([
            'creds' => 'testsite',
            'credu' => 'teuser',
            'credn' => 'Some note',
            'credp' => 'some password',
            'currentgroupid' => $user->primarygroup,
        ]);

        $this->get('/groups/' . $user->primarygroup)
            ->assertOk()
            ->assertSee('testsite')
            ->assertDontSee('No credentials!');

        $this->delete('/settings/resetaccount')->assertRedirect('/groups/' . $user->primarygroup);

        $this->get('/groups/' . $user->primarygroup)
            ->assertOk()
            ->assertSee('No credentials!');
    }
}
