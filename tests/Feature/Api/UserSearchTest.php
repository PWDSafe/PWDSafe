<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use DatabaseMigrations;

    public function testGuestCannotSearch(): void
    {
        $this->getJson('/api/users/search?q=test')->assertUnauthorized();
    }

    public function testSearchRequiresMinimumTwoCharacters(): void
    {
        $this->createAndLoginUser();

        $this->getJson('/api/users/search?q=a')->assertJsonCount(0);
        $this->getJson('/api/users/search')->assertJsonCount(0);
    }

    public function testSearchMatchesByEmail(): void
    {
        $this->createAndLoginUser();
        User::factory()->create(['email' => 'alice@example.com', 'name' => null]);

        $this->getJson('/api/users/search?q=alice')
            ->assertOk()
            ->assertJsonFragment(['email' => 'alice@example.com']);
    }

    public function testSearchMatchesByName(): void
    {
        $this->createAndLoginUser();
        User::factory()->create(['name' => 'Alice Smith', 'email' => 'asmith@example.com']);

        $this->getJson('/api/users/search?q=Alice')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Alice Smith']);
    }

    public function testSearchResultsDoNotIncludeSensitiveFields(): void
    {
        $this->createAndLoginUser();
        User::factory()->create(['name' => 'Bob Jones', 'email' => 'bob@example.com']);

        $result = $this->getJson('/api/users/search?q=Bob')
            ->assertOk()
            ->json('0');

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('privkey', $result);
        $this->assertArrayNotHasKey('pubkey', $result);
    }

    public function testSearchReturnsAtMostTenResults(): void
    {
        $this->createAndLoginUser();
        User::factory()->count(15)->create(['name' => 'Common Name']);

        $this->getJson('/api/users/search?q=Common')
            ->assertOk()
            ->assertJsonCount(10);
    }

    public function testSearchIsPartialMatch(): void
    {
        $this->createAndLoginUser();
        User::factory()->create(['email' => 'charlie@example.com', 'name' => null]);

        $this->getJson('/api/users/search?q=harl')
            ->assertOk()
            ->assertJsonFragment(['email' => 'charlie@example.com']);
    }
}
