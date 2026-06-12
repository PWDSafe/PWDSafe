<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MakeUserAdminTest extends TestCase
{
    use DatabaseMigrations;

    public function testMakeUserAdminGrantsAdminAccess(): void
    {
        User::registerUser('some@email.com', 'password');

        $this->artisan('pwdsafe:make-admin', ['email' => 'some@email.com'])
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'some@email.com')->firstOrFail()->is_admin);
    }

    public function testMakeUserAdminWithUnknownEmailFails(): void
    {
        $this->artisan('pwdsafe:make-admin', ['email' => 'unknown@email.com'])
            ->assertFailed();
    }
}
