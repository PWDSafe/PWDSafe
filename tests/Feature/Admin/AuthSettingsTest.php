<?php

namespace Tests\Feature\Admin;

use App\SystemSetting;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AuthSettingsTest extends TestCase
{
    use DatabaseMigrations;

    public function testGuestIsRedirectedFromAdminPanel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/settings/auth')->assertRedirect('/login');
    }

    public function testNonAdminGetsForbidden(): void
    {
        $user = $this->createAndLoginUser();
        $this->assertFalse($user->is_admin);

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/settings/auth')->assertForbidden();
    }

    public function testAdminCanAccessAuthSettingsPage(): void
    {
        $user = $this->createAdminAndLogin();

        $this->get('/admin/settings/auth')->assertOk();
    }

    public function testAdminRootRedirectsToAuthSettings(): void
    {
        $this->createAdminAndLogin();

        $this->get('/admin')->assertRedirect(route('admin.settings.auth'));
    }

    public function testUpdateAuthSettingsPersistsToDatabase(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/settings/auth', [
            'auth_method' => 'ldap',
            'ldap_server' => 'ldap://dc.example.com',
            'ldap_domain' => 'example.com',
            'ldap_base_dn' => 'OU=Users,DC=example,DC=com',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['key' => 'auth_method', 'value' => 'ldap']);
        $this->assertDatabaseHas('system_settings', ['key' => 'ldap_server', 'value' => 'ldap://dc.example.com']);
        $this->assertDatabaseHas('system_settings', ['key' => 'ldap_domain', 'value' => 'example.com']);
        $this->assertDatabaseHas('system_settings', ['key' => 'ldap_base_dn', 'value' => 'OU=Users,DC=example,DC=com']);
    }

    public function testUpdateRejectsInvalidAuthMethod(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/settings/auth', [
            'auth_method' => 'invalid',
        ])->assertSessionHasErrors('auth_method');
    }

    public function testNonAdminCannotPostSettings(): void
    {
        $user = $this->createAndLoginUser();
        $this->assertFalse($user->is_admin);

        $this->post('/admin/settings/auth', [
            'auth_method' => 'internal',
        ])->assertForbidden();
    }

    public function testDbAuthMethodOverridesEnvConfig(): void
    {
        SystemSetting::set('auth_method', 'ldap');
        SystemSetting::set('ldap_server', 'ldap://db-server.example.com');

        (new \App\Providers\AppServiceProvider(app()))->boot();

        $this->assertTrue(config('ldap.enabled'));
        $this->assertEquals('ldap://db-server.example.com', config('ldap.server'));
    }

    public function testOidcClientSecretIsPreservedWhenBlankOnUpdate(): void
    {
        SystemSetting::set('oidc_client_secret', 's3cr3t');
        $this->createAdminAndLogin();

        $this->post('/admin/settings/auth', [
            'auth_method' => 'oidc',
            'oidc_client_secret' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['key' => 'oidc_client_secret', 'value' => 's3cr3t']);
    }

    public function testOidcBaseUrlFallsBackToLegacyIssuerKey(): void
    {
        SystemSetting::set('oidc_issuer', 'https://legacy.example.com');
        $this->createAdminAndLogin();

        $response = $this->get('/admin/settings/auth');

        $response->assertOk()->assertSee('legacy.example.com');
    }

    public function testInternalAuthMethodDisablesLdapConfig(): void
    {
        SystemSetting::set('auth_method', 'internal');

        (new \App\Providers\AppServiceProvider(app()))->boot();

        $this->assertFalse(config('ldap.enabled'));
    }

    private function createAdminAndLogin(): \App\User
    {
        $user = \App\User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $this->setupVaultSessionForUser($user, 'testing123');

        return $user->fresh();
    }
}
