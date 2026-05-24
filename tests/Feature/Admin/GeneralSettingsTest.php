<?php

namespace Tests\Feature\Admin;

use App\SystemSetting;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use DatabaseMigrations;

    public function testGuestIsRedirectedFromGeneralSettings(): void
    {
        $this->get('/admin/settings/general')->assertRedirect('/login');
    }

    public function testNonAdminGetsForbidden(): void
    {
        $user = $this->createAndLoginUser();
        $this->assertFalse($user->is_admin);

        $this->get('/admin/settings/general')->assertForbidden();
    }

    public function testAdminCanAccessGeneralSettingsPage(): void
    {
        $this->createAdminAndLogin();

        $this->get('/admin/settings/general')->assertOk();
    }

    public function testAdminCanDisableRegistration(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/settings/general', [
            'registration_enabled' => '0',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['key' => 'registration_enabled', 'value' => '0']);
    }

    public function testAdminCanEnableRegistration(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/settings/general', [
            'registration_enabled' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['key' => 'registration_enabled', 'value' => '1']);
    }

    public function testRegistrationIsDisabledWhenSettingIsFalse(): void
    {
        SystemSetting::set('registration_enabled', false);

        (new \App\Providers\AppServiceProvider(app()))->boot();

        $this->assertFalse(config('app.registration_enabled'));
    }

    public function testNonAdminCannotPostSettings(): void
    {
        $user = $this->createAndLoginUser();
        $this->assertFalse($user->is_admin);

        $this->post('/admin/settings/general', [
            'registration_enabled' => '1',
        ])->assertForbidden();
    }

    private function createAdminAndLogin(): \App\User
    {
        $user = \App\User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $this->setupVaultSessionForUser($user, 'testing123');

        return $user->fresh();
    }
}
