<?php

namespace Tests\Feature\Api;

use App\Group;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialSearchTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = User::firstOrFail();
    }

    protected function loginTestUser(): void
    {
        $this->actingAs($this->user);
        $this->setupVaultSessionForUser($this->user, 'password');
    }

    public function testSearchReturnsEmptyArrayWithoutQuery(): void
    {
        $this->loginTestUser();

        $this->getJson('/api/credentials/search')->assertOk()->assertJson([]);
    }

    public function testSearchFindsCredentialByName(): void
    {
        $this->loginTestUser();

        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'GitHub',
            'user' => 'robin',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ]);

        $response = $this->getJson('/api/credentials/search?q=git')->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'GitHub');
    }

    public function testSearchFindsCredentialByUrl(): void
    {
        $this->loginTestUser();

        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'Work GitHub',
            'url' => 'github.com',
            'user' => 'robin',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ]);

        $response = $this->getJson('/api/credentials/search?q=github.com')->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'Work GitHub');
    }

    public function testSearchByDomainMatchesUrlNotName(): void
    {
        $this->loginTestUser();

        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'Work GitHub',
            'url' => 'github.com',
            'user' => 'robin',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ]);

        $response = $this->getJson('/api/credentials/search?domain=github.com')->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'Work GitHub');
        $response->assertJsonPath('0.url', 'github.com');

        $this->getJson('/api/credentials/search?domain=Work')->assertOk()->assertJson([]);
    }

    public function testSearchOnlyReturnsCredentialsFromAccessibleGroups(): void
    {
        $this->loginTestUser();

        $otherGroup = new Group();
        $otherGroup->name = 'Other Team';
        $otherGroup->save();

        $otherUser = User::factory()->create();
        $otherGroup->users()->attach($otherUser, ['permission' => 'admin']);

        $this->actingAs($otherUser);
        $this->setupVaultSessionForUser($otherUser, 'testing123');
        $this->post('/groups/' . $otherGroup->id . '/add', [
            'name' => 'GitLab',
            'user' => 'other',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $otherUser),
        ]);

        $this->loginTestUser();

        $this->getJson('/api/credentials/search?q=GitLab')->assertOk()->assertJson([]);
    }

    public function testShowReturnsCiphertextForOwnCredential(): void
    {
        $this->loginTestUser();

        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'GitHub',
            'user' => 'robin',
            'notes' => 'Work account',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ]);

        $credentialId = \App\Credential::firstOrFail()->id;

        $response = $this->getJson('/api/credentials/' . $credentialId)->assertOk();

        $response->assertJsonStructure(['id', 'name', 'url', 'username', 'notes', 'groupid', 'data']);
        $response->assertJsonPath('name', 'GitHub');
    }

    public function testShowDeniesAccessToCredentialInOtherGroup(): void
    {
        $this->loginTestUser();

        $otherGroup = new Group();
        $otherGroup->name = 'Other Team';
        $otherGroup->save();

        $otherUser = User::factory()->create();
        $otherGroup->users()->attach($otherUser, ['permission' => 'admin']);

        $this->actingAs($otherUser);
        $this->setupVaultSessionForUser($otherUser, 'testing123');
        $this->post('/groups/' . $otherGroup->id . '/add', [
            'name' => 'GitLab',
            'user' => 'other',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $otherUser),
        ]);
        $credentialId = \App\Credential::where('name', 'GitLab')->firstOrFail()->id;

        $this->loginTestUser();

        $this->getJson('/api/credentials/' . $credentialId)->assertForbidden();
    }

    public function testGroupsEndpointListsUserGroups(): void
    {
        $this->loginTestUser();

        $response = $this->getJson('/api/groups')->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $this->user->primarygroup);
        $response->assertJsonPath('0.is_primary', true);
        $response->assertJsonStructure([['id', 'name', 'parent_id', 'permission', 'is_primary']]);
    }
}
