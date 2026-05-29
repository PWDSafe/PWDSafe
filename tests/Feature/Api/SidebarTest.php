<?php

namespace Tests\Feature\Api;

use App\Group;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SidebarTest extends TestCase
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
        $this->getJson('/api/sidebar')->assertUnauthorized();
    }

    public function testSidebarReturnsPrivateAndSharedSections(): void
    {
        $this->loginTestUser();
        $response = $this->getJson('/api/sidebar')->assertOk();

        $response->assertJsonStructure([
            'private' => ['id', 'name', 'url', 'credentialsCount', 'children'],
            'shared',
        ]);

        $this->assertEquals($this->user->primarygroup, $response->json('private.id'));
        $this->assertIsArray($response->json('shared'));
        $this->assertEmpty($response->json('shared'));
    }

    public function testSharedGroupsAppearInSharedSection(): void
    {
        $this->loginTestUser();
        $shared = new Group();
        $shared->name = 'Team Alpha';
        $shared->save();
        $this->user->groups()->attach($shared, ['permission' => 'admin']);

        $response = $this->getJson('/api/sidebar')->assertOk();

        $sharedIds = collect($response->json('shared'))->pluck('id');
        $this->assertContains($shared->id, $sharedIds->all());
    }

    public function testPrivateGroupNotInSharedSection(): void
    {
        $this->loginTestUser();
        $response = $this->getJson('/api/sidebar')->assertOk();

        $sharedIds = collect($response->json('shared'))->pluck('id');
        $this->assertNotContains($this->user->primarygroup, $sharedIds->all());
    }

    public function testSidebarOnlyReturnsGroupsUserBelongsTo(): void
    {
        $this->loginTestUser();
        $otherGroup = new Group();
        $otherGroup->name = 'Other Team';
        $otherGroup->save();

        $response = $this->getJson('/api/sidebar')->assertOk();

        $sharedIds = collect($response->json('shared'))->pluck('id');
        $this->assertNotContains($otherGroup->id, $sharedIds->all());
    }

    public function testSubGroupAppearsAsChildOfParent(): void
    {
        $this->loginTestUser();
        $parent = new Group();
        $parent->name = 'Parent';
        $parent->save();
        $this->user->groups()->attach($parent, ['permission' => 'admin']);

        $child = new Group();
        $child->name = 'Child';
        $child->parent_id = $parent->id;
        $child->save();
        $this->user->groups()->attach($child, ['permission' => 'admin']);

        $response = $this->actingAs($this->user->fresh())->getJson('/api/sidebar')->assertOk();

        $parentNode = collect($response->json('shared'))->firstWhere('id', $parent->id);
        $this->assertNotNull($parentNode);

        $childIds = collect($parentNode['children'])->pluck('id');
        $this->assertContains($child->id, $childIds->all());
    }

    public function testPrivateSubGroupAppearsAsChildOfPrivate(): void
    {
        $this->loginTestUser();
        $privateGroup = Group::find($this->user->primarygroup);

        $child = new Group();
        $child->name = 'My Folder';
        $child->parent_id = $privateGroup->id;
        $child->save();
        $this->user->groups()->attach($child, ['permission' => 'admin']);

        $response = $this->actingAs($this->user->fresh())->getJson('/api/sidebar')->assertOk();

        $childIds = collect($response->json('private.children'))->pluck('id');
        $this->assertContains($child->id, $childIds->all());
    }

    public function testUrlIsCorrectGroupRoute(): void
    {
        $this->loginTestUser();
        $response = $this->getJson('/api/sidebar')->assertOk();

        $privateUrl = $response->json('private.url');
        $this->assertStringContainsString('/groups/' . $this->user->primarygroup, $privateUrl);
    }
}
