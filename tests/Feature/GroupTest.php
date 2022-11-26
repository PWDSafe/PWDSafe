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
        $this->post('/register', [
            'email' => 'some@email.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $this->actingAs(\App\User::first());
        session()->put('password', 'password');
        $this->user = \App\User::first();
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
        $this->assertCount(1, $this->user->groups);
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
        $this->get('/groups/' . $group->id . '/members')->assertOk()->assertSee('Share group');
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
            'pass' => 'The super secret password',
            'notes' => 'Notes',
        ]);

        $this->post('/logout');

        $this->post('/register', [
            'email' => 'second@email.com',
            'password' => 'abitlongersecret',
            'password_confirmation' => 'abitlongersecret'
        ]);
        $this->post('logout');
        $this->from('/login')->post('/login', ['email' => 'some@email.com', 'password' => 'password']);
        $this->post('/groups/' . $group->id . '/members', [
            'username' => 'second@email.com',
            'permission' => 'admin'
        ]);

        $this->assertCount(2, $group->fresh()->users);

        $this->post('logout');
        $this->from('/login')->post('/login', ['email' => 'second@email.com', 'password' => 'abitlongersecret']);
        $seconduser = \App\User::where('email', 'second@email.com')->first();

        $credential = \App\Credential::first();

        $pwd = \App\Encryptedcredential::where('credentialid', $credential->id)
            ->where('userid', $seconduser->id)
            ->first();
        $encryption = app(Encryption::class);

        $decryptedcredential = $encryption->decWithPriv(
            $pwd->data,
            $encryption->dec($seconduser->privkey, 'abitlongersecret')
        );

        $this->assertEquals('The super secret password', $decryptedcredential);
        $this->assertCount(2, \App\Encryptedcredential::all());

        $this->post('/groups/' . $group->id . '/members', [
            'username' => 'does@not.exist',
            'permission' => 'admin'
        ])
            ->assertRedirect()
            ->assertSessionHasErrors();
        $this->post('/groups/' . $group->id . '/members', [
            'username' => 'second@email.com',
            'permission' => 'admin'
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
    }

    public function testUnsharingGroup(): void
    {
        $this->post('/groups/create', [
            'groupname' => 'testgroup',
        ]);

        $group = \App\Group::orderBy('id', 'desc')->first();

        $this->post('/cred/add', [
            'creds' => 'Some site',
            'credu' => 'The username',
            'credp' => 'The super secret password',
            'credn' => 'Notes',
            'currentgroupid' => $group->id,
        ]);

        $this->post('/logout');
        $this->post('/register', [
            'email' => 'second@email.com',
            'password' => 'abitlongersecret',
            'password_confirmation' => 'abitlongersecret'
        ]);
        $user2 = \App\User::where('email', 'second@email.com')->first();
        $this->actingAs(\App\User::first());
        session()->put('password', 'password');

        $this->post('/groups/' . $group->id . '/members', [
            'username' => $user2->email,
            'permission' => 'admin'
        ]);
        $this->assertCount(2, $user2->fresh()->groups);

        $this->delete('/groups/' . $group->id . '/members', ['userid' => $user2->id]);
        $this->assertCount(1, $user2->fresh()->groups);
    }

    public function testUpdateMemberPermission(): void
    {
        $group = new Group();
        $group->name = 'testgroup';
        $group->save();
        User::first()->groups()->attach($group);

        \App\User::registerUser('second@email.com', 'abitlongersecret');
        $user2 = \App\User::where('email', 'second@email.com')->first();

        $this->post('/groups/' . $group->id . '/members', [
            'username' => $user2->email,
            'permission' => 'admin'
        ]);
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
