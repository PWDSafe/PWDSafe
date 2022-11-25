<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        $result = $this->post('/register', [
            'email' => 'some@email.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $result->assertSessionHasErrors('email');
        $this->assertCount(1, \App\User::all());
    }

    public function testRegisterUserAndChangePasswordViaApi(): void
    {
        $this->registerUser();
        $this->loginUser();
        $this->post('/api/pwdchg', [
            'username' => 'some@email.com',
            'old_password' => 'wrongpass',
            'new_password' => 'SecretPassword',
        ])->assertStatus(403);

        $this->post('/api/pwdchg', [
            'username' => 'some@email.com',
            'old_password' => 'password',
            'new_password' => 'SecretPassword',
        ])->assertOk();

        $this->assertTrue(Hash::check('SecretPassword', \App\User::firstOrFail()->password));
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

    public function testRegisterLogYouOutWhenVisitingAnyPage(): void
    {
        $user = [
            'email' => 'some@email.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ];
        $this->post('/register', $user);
        $this->get('/');
        $this->assertGuest();
    }
}
