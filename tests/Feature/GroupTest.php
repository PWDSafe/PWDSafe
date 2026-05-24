<?php

namespace Tests\Feature;

use App\Group;
use App\Helpers\Encryption;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use DatabaseMigrations;

    private \App\User $user;

    public function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = \App\User::first();
        $this->actingAs($this->user);
        $this->setupVaultSessionForUser($this->user, 'password');
    }

    public function testAddingGroup(): void
    {
        $this->assertCount(1, $this->user->groups);
        $this->post('/groups/create', [
            'groupname' => 'testgroup',
        ]);
        $this->assertCount(2, $this->user->fresh()->groups);
        $this->assertDatabaseHas('groups', ['name' => 'testgroup']);
    }

    public function testViewingEmptyGroupList(): void
    {
        $this->get('/groups')->assertOk()->assertSee('You do not have any groups');
    }

    public function testViewingNonEmptyGroupList(): void
    {
        $group = new Group();
        $group->name = 'testgroup';
        $group->save();
        auth()->user()->groups()->attach($group);
        $this->get('/groups')
            ->assertOk()
            ->assertDontSee('You do not have any groups')
            ->assertSee('testgroup');
    }

    public function testVisitingCreate(): void
    {
        $this->get('/groups/create')->assertOk()->assertSee('Create group');
    }

    public function testDeletingGroup(): void
    {
        $this->post('/groups/create', [
            'groupname' => 'testgroup',
        ]);
        $group = \App\Group::orderBy('id', 'desc')->first();
        $this->assertCount(2, $this->user->fresh()->groups);

        $response = $this->get('/groups/' . $group->id . '/delete');
        $response->assertOk();
        $response->assertSee('Are you sure');

        $this->delete('/groups/' . $group->id);
        $this->assertDatabaseMissing('groups', ['name' => 'testgroup']);
        $this->assertCount(1, $this->user->fresh()->groups);
    }

    public function testDeletingPrimaryGroup(): void
    {
        $group = \App\Group::first();

        $response = $this->get('/groups/' . $group->id . '/delete');
        $response->assertStatus(403);

        $this->post('/groups/' . $group->id . '/delete', []);
        $this->assertDatabaseHas('groups', ['id' => $group->id]);
        $this->assertCount(1, $this->user->fresh()->groups);
    }

    public function testRenamingGroup(): void
    {
        $this->assertCount(1, $this->user->fresh()->groups);
        $this->post('/groups/create', [
            'groupname' => 'testgroup',
        ]);
        $this->assertCount(2, $this->user->fresh()->groups);
        $this->assertDatabaseHas('groups', ['name' => 'testgroup']);

        $group = \App\Group::orderBy('id', 'desc')->first();

        $this->get("/groups/{$group->id}/name")->assertSee('Group name');
        $this->post('/groups/' . $group->id . '/name', [
            'groupname' => 'new name',
        ])->assertRedirect('/groups/' . $group->id);

        $this->assertDatabaseMissing('groups', ['name' => 'testgroup']);
        $this->assertDatabaseHas('groups', ['name' => 'new name']);

        $this->postJson('/groups/' . $group->id . '/name', [
            'groupname' => 'new name',
        ])->assertOk()->assertJson(['status' => 'OK']);
    }

    public function testVisitingGroupMembers(): void
    {
        $this->post('/groups/create', [
            'groupname' => 'testgroup',
        ]);

        $group = \App\Group::orderBy('id', 'desc')->first();
        $this->get('/groups/' . $group->id . '/members')->assertOk()->assertSee('add-group-member', false);
    }

    public function testSharingGroup(): void
    {
        $this->post('/groups/create', [
            'groupname' => 'testgroup',
        ]);

        $group = \App\Group::orderBy('id', 'desc')->first();

        $this->post("/groups/{$group->id}/add", [
            'site' => 'Some site',
            'user' => 'The username',
            'notes' => 'Notes',
            'encrypted' => $this->encryptedPayloadForUsers('The super secret password', $this->user),
        ]);

        $this->post('/logout');

        User::registerUser('second@email.com', 'abitlongersecret');
        $seconduser = \App\User::where('email', 'second@email.com')->first();
        $this->from('/login')->post('/login', ['email' => 'some@email.com', 'password' => 'password']);

        // Step 1: prepare — validate new member and get credentials + their pubkey
        $prepare = $this->postJson("/api/groups/{$group->id}/members/prepare", [
            'user_id' => $seconduser->id,
            'permission' => 'admin',
        ])->assertOk()->json();

        $this->assertEquals($seconduser->id, $prepare['user']['id']);

        $encryption = app(Encryption::class);
        $reEncrypted = array_map(function ($cred) use ($encryption, $seconduser) {
            $plaintext = $encryption->decWithPriv($cred['data'], $this->user->fresh()->decryptPrivkey());
            return [
                'credentialid' => $cred['id'],
                'data' => $encryption->encWithPub($plaintext, $seconduser->pubkey),
            ];
        }, $prepare['credentials']);

        // Step 2: confirm — attach user and store re-encrypted credentials
        $this->postJson("/api/groups/{$group->id}/members/confirm", [
            'user_id' => $seconduser->id,
            'permission' => 'admin',
            'encrypted' => $reEncrypted,
        ])->assertOk();

        $this->assertCount(2, $group->fresh()->users);

        $credential = \App\Credential::first();
        $pwd = \App\Encryptedcredential::where('credentialid', $credential->id)
            ->where('userid', $seconduser->id)
            ->first();

        $vaultKey = Encryption::deriveVaultKey('abitlongersecret', $seconduser->fresh()->privkey_salt);
        $decryptedcredential = $encryption->decWithPriv(
            $pwd->data,
            $encryption->decV2($seconduser->fresh()->privkey, $vaultKey)
        );

        $this->assertEquals('The super secret password', $decryptedcredential);
        $this->assertCount(2, \App\Encryptedcredential::all());

        // prepare returns an error for non-existent users
        $this->postJson("/api/groups/{$group->id}/members/prepare", [
            'user_id' => 999999,
            'permission' => 'admin',
        ])->assertStatus(422);

        // prepare returns an error when user is already a member
        $this->postJson("/api/groups/{$group->id}/members/prepare", [
            'user_id' => $seconduser->id,
            'permission' => 'admin',
        ])->assertStatus(422);
    }

    public function testUnsharingGroup(): void
    {
        $this->post('/groups/create', [
            'groupname' => 'testgroup',
        ]);

        $group = \App\Group::orderBy('id', 'desc')->first();

        $this->post('/logout');
        User::registerUser('second@email.com', 'abitlongersecret');
        $user2 = \App\User::where('email', 'second@email.com')->first();
        $user1 = \App\User::where('email', 'some@email.com')->first();
        $this->actingAs($user1);
        $this->setupVaultSessionForUser($user1, 'password');

        $this->postJson("/api/groups/{$group->id}/members/prepare", [
            'user_id' => $user2->id,
            'permission' => 'admin',
        ])->assertOk();

        $this->postJson("/api/groups/{$group->id}/members/confirm", [
            'user_id' => $user2->id,
            'permission' => 'admin',
            'encrypted' => [],
        ])->assertOk();

        $this->assertCount(2, $user2->fresh()->groups);

        $this->delete('/groups/' . $group->id . '/members', ['userid' => $user2->id]);
        $this->assertCount(1, $user2->fresh()->groups);
    }

    public function testUpdateMemberPermission(): void
    {
        $group = new Group();
        $group->name = 'testgroup';
        $group->save();
        User::first()->groups()->attach($group, ['permission' => 'admin']);

        \App\User::registerUser('second@email.com', 'abitlongersecret');
        $user2 = \App\User::where('email', 'second@email.com')->first();

        $this->postJson("/api/groups/{$group->id}/members/prepare", [
            'user_id' => $user2->id,
            'permission' => 'admin',
        ])->assertOk();

        $this->postJson("/api/groups/{$group->id}/members/confirm", [
            'user_id' => $user2->id,
            'permission' => 'admin',
            'encrypted' => [],
        ])->assertOk();

        $this->assertCount(2, $user2->fresh()->groups);

        $this->patch('/groups/' . $group->id . '/members/' . $user2->id, [
            'permission' => 'write'
        ])->assertOk();

        $this->post('/logout');
        $this->from('/login')
            ->post('/login', [
                'email' => 'second@email.com',
                'password' => 'abitlongersecret'
            ]);
        $this->get('/groups/' . $group->id . '/members')->assertForbidden();
    }
}
