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
        \App\User::registerUser('some@email.com', 'password');
        $this->user = \App\User::first();
        $this->actingAs($this->user);
        $this->setupVaultSessionForUser($this->user, 'password');
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
            'notes' => 'Some notes here',
            'encrypted' => $this->encryptedPayloadForUsers('The super secret password', $this->user),
        ]);

        $this->post("/groups/{$this->user->primarygroup}/add", [
            'site' => 'Site2',
            'user' => 'The username',
            'notes' => 'No notes here',
            'encrypted' => $this->encryptedPayloadForUsers('The super secret password', $this->user),
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
