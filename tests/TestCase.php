<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function registerUser(): void {
        $this->post('/register', [
            'email' => 'some@email.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $this->post('/logout');
    }

    protected function loginUser(): \Illuminate\Testing\TestResponse
    {
        return $this->from('/login')
            ->post('/login', [
            'email' => 'some@email.com',
            'password' => 'password'
        ]);
    }

    protected function createAndLoginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        session()->put('password', 'testing123');

        return $user;
    }
}
