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

    public function testGroupsIndexRedirectsToPrimaryGroup(): void
    {
        $this->get('/groups')->assertRedirect(route('group', $this->user->primarygroup));
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

    public function testCreatingSubGroup(): void
    {
        $this->post('/groups/create', ['groupname' => 'parent']);
        $parent = \App\Group::orderBy('id', 'desc')->first();

        $this->get('/groups/' . $parent->id . '/subgroups/create')->assertOk()->assertSee('Sub-group of');

        $this->post('/groups/' . $parent->id . '/subgroups/create', ['groupname' => 'child']);

        $child = \App\Group::orderBy('id', 'desc')->first();
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertCount(3, $this->user->fresh()->groups);
    }

    public function testNonAdminCannotCreateSubGroup(): void
    {
        $this->post('/groups/create', ['groupname' => 'parent']);
        $parent = \App\Group::orderBy('id', 'desc')->first();

        \App\User::registerUser('second@email.com', 'password2');
        $user2 = \App\User::where('email', 'second@email.com')->first();
        $parent->users()->attach($user2, ['permission' => 'write']);

        $this->actingAs($user2);
        $this->get('/groups/' . $parent->id . '/subgroups/create')->assertForbidden();
        $this->post('/groups/' . $parent->id . '/subgroups/create', ['groupname' => 'child'])->assertForbidden();
    }

    public function testDeletingGroupWithChildrenIsBlocked(): void
    {
        $this->post('/groups/create', ['groupname' => 'parent']);
        $parent = \App\Group::orderBy('id', 'desc')->first();

        $this->post('/groups/' . $parent->id . '/subgroups/create', ['groupname' => 'child']);

        $this->delete('/groups/' . $parent->id)->assertForbidden();
        $this->assertDatabaseHas('groups', ['name' => 'parent']);
    }

    public function testSubGroupAppearsInParentView(): void
    {
        $this->post('/groups/create', ['groupname' => 'parent']);
        $parent = \App\Group::orderBy('id', 'desc')->first();

        $this->post('/groups/' . $parent->id . '/subgroups/create', ['groupname' => 'child']);

        $this->get('/groups/' . $parent->id)
            ->assertOk()
            ->assertSee('Sub-groups')
            ->assertSee('child');
    }

    public function testBreadcrumbsForNestedGroup(): void
    {
        $this->post('/groups/create', ['groupname' => 'grandparent'])->assertRedirect();
        $grandparent = \App\Group::orderBy('id', 'desc')->first();

        $this->actingAs($this->user->fresh());
        $this->post('/groups/' . $grandparent->id . '/subgroups/create', ['groupname' => 'parent'])->assertRedirect();
        $parent = \App\Group::orderBy('id', 'desc')->first();

        $this->actingAs($this->user->fresh());
        $this->post('/groups/' . $parent->id . '/subgroups/create', ['groupname' => 'child'])->assertRedirect();
        $child = \App\Group::orderBy('id', 'desc')->first();

        // Refresh the user to clear stale groups cache before the view policy check
        $this->actingAs($this->user->fresh());
        $this->get('/groups/' . $child->id)
            ->assertOk()
            ->assertSee('grandparent')
            ->assertSee('parent')
            ->assertSee('child');
    }

    public function testCreatingSubGroupUnderPrivateGroup(): void
    {
        $privateGroup = \App\Group::find($this->user->primarygroup);

        $this->get('/groups/' . $privateGroup->id . '/subgroups/create')
            ->assertOk()
            ->assertSee('Sub-group of')
            ->assertSee('Private');

        $this->post('/groups/' . $privateGroup->id . '/subgroups/create', ['groupname' => 'my folder']);

        $child = \App\Group::orderBy('id', 'desc')->first();
        $this->assertEquals($privateGroup->id, $child->parent_id);
        $this->assertCount(2, $this->user->fresh()->groups);
    }

    public function testSubGroupAppearsInPrivateGroupView(): void
    {
        $privateGroup = \App\Group::find($this->user->primarygroup);

        $this->post('/groups/' . $privateGroup->id . '/subgroups/create', ['groupname' => 'my folder']);

        $this->get('/groups/' . $privateGroup->id)
            ->assertOk()
            ->assertSee('Sub-groups')
            ->assertSee('my folder');
    }

    public function testBreadcrumbsForPrivateSubGroup(): void
    {
        $privateGroup = \App\Group::find($this->user->primarygroup);

        $this->post('/groups/' . $privateGroup->id . '/subgroups/create', ['groupname' => 'my folder'])->assertRedirect();
        $child = \App\Group::orderBy('id', 'desc')->first();

        $this->actingAs($this->user->fresh());
        $this->get('/groups/' . $child->id)
            ->assertOk()
            ->assertSee('Private')
            ->assertSee('my folder');
    }

    public function testIsInPrivateTreeReturnsTrueForPrimaryGroup(): void
    {
        $privateGroup = \App\Group::find($this->user->primarygroup);
        $this->assertTrue($privateGroup->isInPrivateTree($this->user));
    }

    public function testIsInPrivateTreeReturnsTrueForPrivateSubGroup(): void
    {
        $privateGroup = \App\Group::find($this->user->primarygroup);
        $child = new Group();
        $child->name = 'sub';
        $child->parent_id = $privateGroup->id;
        $child->save();

        $this->assertTrue($child->isInPrivateTree($this->user));
    }

    public function testIsInPrivateTreeReturnsFalseForSharedGroup(): void
    {
        $shared = new Group();
        $shared->name = 'shared';
        $shared->save();
        $this->user->groups()->attach($shared, ['permission' => 'admin']);

        $this->assertFalse($shared->isInPrivateTree($this->user));
    }

    public function testManageMembersPolicyBlocksPrimaryGroup(): void
    {
        $privateGroup = \App\Group::find($this->user->primarygroup);
        $this->assertFalse($this->user->can('manageMembers', $privateGroup));
    }

    public function testManageMembersPolicyBlocksPrivateSubGroupByDefault(): void
    {
        $privateGroup = \App\Group::find($this->user->primarygroup);
        $child = new Group();
        $child->name = 'sub';
        $child->parent_id = $privateGroup->id;
        $child->save();
        $this->user->groups()->attach($child, ['permission' => 'admin']);

        $this->assertFalse($this->user->fresh()->can('manageMembers', $child));
    }

    public function testManageMembersPolicyAllowsSharedGroup(): void
    {
        $shared = new Group();
        $shared->name = 'shared';
        $shared->save();
        $this->user->groups()->attach($shared, ['permission' => 'admin']);

        $this->assertTrue($this->user->fresh()->can('manageMembers', $shared));
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
            'name' => 'Some site',
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

    public function testMemberRemovalConfirmation(): void
    {
        $this->post('/groups/create', ['groupname' => 'testgroup']);

        $group = \App\Group::orderBy('id', 'desc')->first();
        \App\User::registerUser('second@email.com', 'password');
        $user2 = \App\User::where('email', 'second@email.com')->first();

        $group->users()->attach($user2, ['permission' => 'read']);
        $response = $this->get('/groups/' . $group->id . '/members/' . $user2->id . '/delete');

        $response->assertOk();
        $response->assertSee('Are you sure');

        $this->delete('/groups/' . $group->id . '/members', ['userid' => $user2->id]);
        $this->assertFalse($group->fresh()->users->contains($user2));
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
