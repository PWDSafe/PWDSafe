<?php

namespace Tests\Feature\Api;

use App\Group;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialsTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = User::first();
    }

    protected function loginTestUser(): void
    {
        $this->actingAs($this->user);
        $this->setupVaultSessionForUser($this->user, 'password');
    }

    public function testGuestIsRedirected(): void
    {
        $this->getJson('/api/groups/' . $this->user->primarygroup . '/credentials')->assertUnauthorized();
    }

    public function testReturnsCredentialsForGroup(): void
    {
        $this->loginTestUser();

        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'GitHub',
            'user' => 'robin',
            'notes' => 'Work account',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ]);

        $response = $this->getJson('/api/groups/' . $this->user->primarygroup . '/credentials')->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'GitHub');
        $response->assertJsonPath('0.username', 'robin');
        $response->assertJsonPath('0.groupid', $this->user->primarygroup);
        $response->assertJsonStructure([['id', 'name', 'url', 'username', 'notes', 'groupid', 'group' => ['id', 'name']]]);
    }

    public function testReturnsEmptyArrayForGroupWithNoCredentials(): void
    {
        $this->loginTestUser();

        $response = $this->getJson('/api/groups/' . $this->user->primarygroup . '/credentials')->assertOk();

        $response->assertJson([]);
        $this->assertCount(0, $response->json());
    }

    public function testCannotAccessCredentialsForGroupUserDoesNotBelongTo(): void
    {
        $this->loginTestUser();

        $otherGroup = new Group();
        $otherGroup->name = 'Other Team';
        $otherGroup->save();

        $this->getJson('/api/groups/' . $otherGroup->id . '/credentials')->assertForbidden();
    }

    public function testCredentialsAreOrderedByName(): void
    {
        $this->loginTestUser();

        foreach (['Zebra', 'Apple', 'Mango'] as $site) {
            $this->post('/groups/' . $this->user->primarygroup . '/add', [
                'name' => $site,
                'user' => 'user',
                'notes' => '',
                'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
            ]);
        }

        $response = $this->getJson('/api/groups/' . $this->user->primarygroup . '/credentials')->assertOk();

        $names = array_column($response->json(), 'name');
        $this->assertEquals(['Apple', 'Mango', 'Zebra'], $names);
    }
}
