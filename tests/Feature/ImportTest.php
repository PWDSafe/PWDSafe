<?php

namespace Tests\Feature;

use App\Credential;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = User::first();
        Auth::loginUsingId($this->user->id);
        $this->setupVaultSessionForUser($this->user, 'password');
    }

    public function testImportingCredentials(): void
    {
        $this->postJson('/import', [
            'group' => $this->user->primarygroup,
            'credentials' => [
                [
                    'name' => 'Some site',
                    'url' => 'https://example.com',
                    'username' => 'A username',
                    'notes' => 'And some notes',
                    'encrypted' => $this->encryptedPayloadForUsers('A password', $this->user),
                ],
                [
                    'name' => 'Second site',
                    'username' => 'the@user.com',
                    'notes' => '',
                    'encrypted' => $this->encryptedPayloadForUsers('StrangeP@ssW0rd!', $this->user),
                ],
            ],
        ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->assertCount(2, Credential::all());
        $this->assertDatabaseHas('credentials', ['name' => 'Some site', 'url' => 'https://example.com']);
        $this->assertDatabaseHas('credentials', ['name' => 'Second site', 'url' => null]);
    }

    public function testImportingSkipsMalformedRowsClientSide(): void
    {
        // The client filters malformed rows before posting, so the server
        // receives only valid credentials. An empty credentials array is valid.
        $this->postJson('/import', [
            'group' => $this->user->primarygroup,
            'credentials' => [],
        ])
            ->assertOk()
            ->assertJson(['count' => 0]);

        $this->assertCount(0, Credential::all());
    }

    public function testImportingRequiresGroupMembership(): void
    {
        User::registerUser('other@email.com', 'password');
        $otherUser = User::where('email', 'other@email.com')->first();

        $this->postJson('/import', [
            'group' => $otherUser->primarygroup,
            'credentials' => [
                [
                    'name' => 'Test',
                    'username' => 'user',
                    'notes' => '',
                    'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
                ],
            ],
        ])->assertForbidden();
    }

    public function testImportingValidatesPayload(): void
    {
        $this->postJson('/import', [
            'group' => $this->user->primarygroup,
            'credentials' => [
                ['name' => 'Missing username and encrypted'],
            ],
        ])->assertUnprocessable();
    }
}
