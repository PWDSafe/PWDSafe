<?php

namespace Tests\Feature\Api;

use App\Credential;
use App\Group;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialsControllerTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = User::firstOrFail();
        $this->actingAs($this->user);
        $this->setupVaultSessionForUser($this->user, 'password');
    }

    public function testStoreCreatesCredentialInOwnGroup(): void
    {
        $group = Group::find($this->user->primarygroup);

        $response = $this->postJson("/api/groups/{$group->id}/credentials", [
            'site' => 'GitHub',
            'user' => 'robin',
            'notes' => 'Work account',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertCreated();

        $response->assertJsonStructure(['id', 'site', 'username', 'notes', 'groupid']);
        $response->assertJsonPath('site', 'GitHub');
        $response->assertJsonPath('username', 'robin');
        $response->assertJsonPath('groupid', $group->id);

        $this->assertDatabaseHas('credentials', ['site' => 'GitHub', 'username' => 'robin', 'groupid' => $group->id]);

        $credential = Credential::where('site', 'GitHub')->firstOrFail();
        $this->assertDatabaseHas('encryptedcredentials', [
            'credentialid' => $credential->id,
            'userid' => $this->user->id,
        ]);
    }

    public function testStoreCreatesCredentialWithoutNotes(): void
    {
        $group = Group::find($this->user->primarygroup);

        $response = $this->postJson("/api/groups/{$group->id}/credentials", [
            'site' => 'GitHub',
            'user' => 'robin',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertCreated();

        $response->assertJsonPath('notes', null);
        $this->assertDatabaseHas('credentials', ['site' => 'GitHub', 'notes' => null]);
    }

    public function testStoreDeniedWithoutWritePermission(): void
    {
        $otherGroup = Group::create(['name' => 'Other Team']);
        $this->user->groups()->attach($otherGroup, ['permission' => 'read']);

        $this->postJson("/api/groups/{$otherGroup->id}/credentials", [
            'site' => 'GitLab',
            'user' => 'robin',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertForbidden();

        $this->assertDatabaseMissing('credentials', ['site' => 'GitLab']);
    }

    public function testStoreValidatesRequiredFields(): void
    {
        $group = Group::find($this->user->primarygroup);

        $this->postJson("/api/groups/{$group->id}/credentials", [])->assertStatus(422);
    }

    public function testMoveCredentialToAnotherGroup(): void
    {
        $sourceGroup = Group::find($this->user->primarygroup);
        $destinationGroup = Group::create(['name' => 'Destination']);
        $this->user->groups()->attach($destinationGroup, ['permission' => 'admin']);

        $response = $this->postJson("/api/groups/{$sourceGroup->id}/credentials", [
            'site' => 'GitHub',
            'user' => 'robin',
            'notes' => 'Work account',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertCreated();

        $credentialId = $response->json('id');

        $moveResponse = $this->postJson("/api/credentials/{$credentialId}/move", [
            'group_id' => $destinationGroup->id,
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertCreated();

        $moveResponse->assertJsonStructure(['id', 'site', 'username', 'notes', 'groupid']);
        $moveResponse->assertJsonPath('site', 'GitHub');
        $moveResponse->assertJsonPath('username', 'robin');
        $moveResponse->assertJsonPath('notes', 'Work account');
        $moveResponse->assertJsonPath('groupid', $destinationGroup->id);

        $newCredentialId = $moveResponse->json('id');

        $this->assertDatabaseMissing('credentials', ['id' => $credentialId]);
        $this->assertDatabaseMissing('encryptedcredentials', ['credentialid' => $credentialId]);
        $this->assertDatabaseHas('credentials', [
            'id' => $newCredentialId,
            'site' => 'GitHub',
            'username' => 'robin',
            'groupid' => $destinationGroup->id,
        ]);
        $this->assertDatabaseHas('encryptedcredentials', [
            'credentialid' => $newCredentialId,
            'userid' => $this->user->id,
        ]);
    }

    public function testMoveDeniedWithoutWritePermissionOnDestination(): void
    {
        $sourceGroup = Group::find($this->user->primarygroup);
        $destinationGroup = Group::create(['name' => 'Destination']);
        $this->user->groups()->attach($destinationGroup, ['permission' => 'read']);

        $response = $this->postJson("/api/groups/{$sourceGroup->id}/credentials", [
            'site' => 'GitHub',
            'user' => 'robin',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertCreated();

        $credentialId = $response->json('id');

        $this->postJson("/api/credentials/{$credentialId}/move", [
            'group_id' => $destinationGroup->id,
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertForbidden();

        $this->assertDatabaseHas('credentials', ['id' => $credentialId]);
    }

    public function testMoveDeniedWithoutWritePermissionOnSource(): void
    {
        $sourceGroup = Group::create(['name' => 'Source']);
        $this->user->groups()->attach($sourceGroup, ['permission' => 'read']);

        $destinationGroup = Group::find($this->user->primarygroup);

        $credential = Credential::create([
            'groupid' => $sourceGroup->id,
            'site' => 'GitHub',
            'username' => 'robin',
        ]);

        $this->postJson("/api/credentials/{$credential->id}/move", [
            'group_id' => $destinationGroup->id,
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertForbidden();

        $this->assertDatabaseHas('credentials', ['id' => $credential->id]);
    }

    public function testMoveValidatesRequiredFields(): void
    {
        $sourceGroup = Group::find($this->user->primarygroup);

        $response = $this->postJson("/api/groups/{$sourceGroup->id}/credentials", [
            'site' => 'GitHub',
            'user' => 'robin',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertCreated();

        $credentialId = $response->json('id');

        $this->postJson("/api/credentials/{$credentialId}/move", [])->assertStatus(422);

        $this->postJson("/api/credentials/{$credentialId}/move", [
            'group_id' => $sourceGroup->id,
            'encrypted' => [['userid' => 999999, 'data' => 'secret']],
        ])->assertStatus(422);
    }

    public function testMoveValidatesGroupIdExists(): void
    {
        $sourceGroup = Group::find($this->user->primarygroup);

        $response = $this->postJson("/api/groups/{$sourceGroup->id}/credentials", [
            'site' => 'GitHub',
            'user' => 'robin',
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertCreated();

        $credentialId = $response->json('id');

        $this->postJson("/api/credentials/{$credentialId}/move", [
            'group_id' => 999999,
            'encrypted' => $this->encryptedPayloadForUsers('secret', $this->user),
        ])->assertStatus(422);
    }
}
