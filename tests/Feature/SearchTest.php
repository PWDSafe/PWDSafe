<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use DatabaseMigrations;

    private \App\User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->post('/register', [
            'email' => 'some@email.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $this->actingAs(\App\User::first());
        session()->put('password', 'password');
        $this->user = \App\User::first();
    }

    public function testSearchingShouldReturnEmptyPage(): void
    {
        $this->get('/search/something')
            ->assertOk()
            ->assertSee('No credentials found');
    }

    public function testSearchByPost(): void
    {
        $this->post('/search', ['search' => 'Something'])
            ->assertRedirect('/search/Something');
    }

    public function testSearchingOneItem(): void
    {
        $this->post("/groups/{$this->user->primarygroup}/add", [
            'site' => 'Site1',
            'user' => 'The username',
            'pass' => 'The super secret password',
            'notes' => 'Some notes here',
        ]);

        $this->post("/groups/{$this->user->primarygroup}/add", [
            'site' => 'Site2',
            'user' => 'The username',
            'pass' => 'The super secret password',
            'notes' => 'No notes here',
        ]);

        $this->get('/search/Site2')
            ->assertOk()
            ->assertSee('Site2')
            ->assertDontSee('Site1');
        $this->get('/search/site')
            ->assertOk()
            ->assertSee('Site2')
            ->assertSee('Site1');
    }
}
