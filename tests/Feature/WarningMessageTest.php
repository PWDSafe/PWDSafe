<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WarningMessageTest extends TestCase
{
    use DatabaseMigrations;

    public function testSeeMessageWhenLogin(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password');

        $this->get('/groups/' . $user->privategroup)
            ->assertOk()
            ->assertSee('</warning-message>', false);
    }

    public function testDontSeeMessageWhenLoginAndHaveAcknowledged(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password');

        $this->post('/settings/warningmessage', ['accept' => true])->assertOk();

        $this->get('/groups/' . $user->privategroup)
            ->assertOk()
            ->assertDontSee('</warning-message>', false);
    }
}
