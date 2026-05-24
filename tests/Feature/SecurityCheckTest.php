<?php

namespace Tests\Feature;

use App\Helpers\Encryption;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SecurityCheckTest extends TestCase
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

    public function testSecurityCheckPageLoads(): void
    {
        $this->get('/securitycheck')->assertOk();
    }

    public function testApiReturnsEmptyForNoCredentials(): void
    {
        $this->getJson('/api/securitycheck')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function testApiReturnsCredentialsForDecryption(): void
    {
        $this->post("/groups/{$this->user->primarygroup}/add", [
            'site' => 'Site1',
            'user' => 'The username',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('password123', $this->user),
        ]);

        $this->post("/groups/{$this->user->primarygroup}/add", [
            'site' => 'Site2',
            'user' => 'The username',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('different_password', $this->user),
        ]);

        $response = $this->getJson('/api/securitycheck')
            ->assertOk()
            ->json();

        $this->assertCount(2, $response);

        $encryption = app(Encryption::class);
        $passwords = array_map(
            fn ($cred) => $encryption->decWithPriv($cred['data'], $this->user->fresh()->decryptPrivkey()),
            $response
        );

        $this->assertContains('password123', $passwords);
        $this->assertContains('different_password', $passwords);
    }

    public function testApiReturnsBothCredentialsWithSamePassword(): void
    {
        $this->post("/groups/{$this->user->primarygroup}/add", [
            'site' => 'Site1',
            'user' => 'The username',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('shared_password', $this->user),
        ]);

        $this->post("/groups/{$this->user->primarygroup}/add", [
            'site' => 'Site2',
            'user' => 'The username',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('shared_password', $this->user),
        ]);

        $response = $this->getJson('/api/securitycheck')
            ->assertOk()
            ->json();

        $this->assertCount(2, $response);

        $encryption = app(Encryption::class);
        $passwords = array_map(
            fn ($cred) => $encryption->decWithPriv($cred['data'], $this->user->fresh()->decryptPrivkey()),
            $response
        );

        $this->assertSame($passwords[0], $passwords[1]);
    }
}
