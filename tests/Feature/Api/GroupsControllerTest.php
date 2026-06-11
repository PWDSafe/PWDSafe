<?php

namespace Tests\Feature\Api;

use App\Group;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GroupsControllerTest extends TestCase
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

    public function testStoreCreatesTopLevelGroup(): void
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'New Group',
        ])->assertCreated();

        $response->assertJsonStructure(['id', 'name', 'parent_id']);
        $response->assertJsonPath('name', 'New Group');
        $response->assertJsonPath('parent_id', null);

        $this->assertDatabaseHas('groups', ['name' => 'New Group']);

        $group = Group::where('name', 'New Group')->firstOrFail();
        $this->assertTrue($this->user->fresh()->groups->contains('id', $group->id));
        $this->assertEquals('admin', $this->user->fresh()->groups->find($group->id)->pivot->permission);
    }

    public function testStoreCreatesSubGroupWhenAdminOnParent(): void
    {
        $parent = Group::create(['name' => 'Parent']);
        $this->user->groups()->attach($parent, ['permission' => 'admin']);

        $response = $this->postJson('/api/groups', [
            'name' => 'Child',
            'parent_id' => $parent->id,
        ])->assertCreated();

        $response->assertJsonPath('parent_id', $parent->id);
        $this->assertDatabaseHas('groups', ['name' => 'Child', 'parent_id' => $parent->id]);
    }

    public function testStoreSubGroupDeniedWithoutAdminPermission(): void
    {
        $parent = Group::create(['name' => 'Parent']);
        $this->user->groups()->attach($parent, ['permission' => 'write']);

        $this->postJson('/api/groups', [
            'name' => 'Child',
            'parent_id' => $parent->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('groups', ['name' => 'Child']);
    }

    public function testStoreValidatesRequiredName(): void
    {
        $this->postJson('/api/groups', [])->assertStatus(422);
    }

    public function testStoreValidatesParentIdExists(): void
    {
        $this->postJson('/api/groups', [
            'name' => 'Child',
            'parent_id' => 999999,
        ])->assertStatus(422);
    }
}
