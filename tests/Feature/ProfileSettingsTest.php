<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use DatabaseMigrations;

    public function testSettingsPageIsAccessible(): void
    {
        $user = $this->createAndLoginUser();

        $this->get('/settings')->assertOk()->assertSee('Settings');
    }

    public function testSettingsPageShowsNamePromptWhenNameIsNull(): void
    {
        $user = User::factory()->create(['name' => null]);
        $this->actingAs($user);
        $this->setupVaultSessionForUser($user, 'testing123');

        $this->get('/settings')->assertSeeText("You haven't set your display name yet");
    }

    public function testSettingsPageDoesNotShowNamePromptWhenNameIsSet(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);
        $this->actingAs($user);
        $this->setupVaultSessionForUser($user, 'testing123');

        $this->get('/settings')->assertDontSee("You haven't set your display name yet");
    }

    public function testUserCanUpdateName(): void
    {
        $user = $this->createAndLoginUser();

        $this->post('/settings', [
            'change_type' => 'profile',
            'name' => 'John Doe',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertEquals('John Doe', $user->fresh()->name);
    }

    public function testUserCannotClearName(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $this->actingAs($user);
        $this->setupVaultSessionForUser($user, 'testing123');

        $this->post('/settings', [
            'change_type' => 'profile',
            'name' => '',
        ])->assertSessionHasErrors('name');

        $this->assertEquals('Old Name', $user->fresh()->name);
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('/settings')->assertRedirect('/login');
        $this->post('/settings', ['change_type' => 'profile'])->assertRedirect('/login');
    }

    public function testOldChangepwdRouteNoLongerExists(): void
    {
        $user = $this->createAndLoginUser();

        $this->get('/changepwd')->assertNotFound();
    }
}
