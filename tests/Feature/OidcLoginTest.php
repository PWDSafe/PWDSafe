<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class OidcLoginTest extends TestCase
{
    use DatabaseMigrations;

    private function mockSocialiteUser(string $email, ?string $name): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);

        $provider = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $provider->shouldReceive('setScopes')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('/'));
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('oidc')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $socialite);
    }

    public function testNewUserIsProvisionedWithNameFromOidc(): void
    {
        $this->mockSocialiteUser('jane@example.com', 'Jane Doe');

        $this->get('/auth/oidc/callback');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'auth_source' => 'oidc',
        ]);
    }

    public function testNewUserFallsBackToEmailWhenNameIsAbsent(): void
    {
        $this->mockSocialiteUser('jane@example.com', null);

        $this->get('/auth/oidc/callback');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'jane@example.com',
            'auth_source' => 'oidc',
        ]);
    }

    public function testExistingUserNameIsUpdatedOnLogin(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'name' => 'Old Name', 'auth_source' => 'oidc']);

        $this->mockSocialiteUser('jane@example.com', 'Jane Doe');

        $this->get('/auth/oidc/callback');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
    }

    public function testExistingUserNameIsNotClearedWhenOidcReturnsNoName(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'name' => 'Old Name', 'auth_source' => 'oidc']);

        $this->mockSocialiteUser('jane@example.com', null);

        $this->get('/auth/oidc/callback');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Old Name',
        ]);
    }
}
