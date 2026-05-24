<?php

namespace App\Providers;

use App\Helpers\Encryption;
use App\Helpers\LdapAuthentication;
use App\SystemSetting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton(Encryption::class, function () {
            return new Encryption();
        });

        app()->singleton(LdapAuthentication::class, function () {
            return new LdapAuthentication();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register the OIDC Socialite driver
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('oidc', \SocialiteProviders\OIDC\Provider::class);
        });

        if (Schema::hasTable('system_settings')) {
            $authMethod = SystemSetting::get('auth_method', 'internal');

            config([
                'app.auth_method' => $authMethod,
                'app.registration_enabled' => (bool) SystemSetting::get('registration_enabled', true),
                'ldap.enabled' => $authMethod === 'ldap',
                'ldap.server' => SystemSetting::get('ldap_server', config('ldap.server')),
                'ldap.domain' => SystemSetting::get('ldap_domain', config('ldap.domain')),
                'ldap.basedn' => SystemSetting::get('ldap_base_dn', config('ldap.basedn')),
                'ldap.openldap' => (bool) SystemSetting::get('ldap_use_openldap', config('ldap.openldap')),
                'ldap.trust_certificate' => (bool) SystemSetting::get('ldap_trust_certificate', false),
                'ldap.certificate' => SystemSetting::get('ldap_certificate', config('ldap.certificate')),
            ]);

            if ($authMethod === 'oidc') {
                config([
                    'services.oidc.client_id' => SystemSetting::get('oidc_client_id', config('services.oidc.client_id')),
                    'services.oidc.client_secret' => SystemSetting::get('oidc_client_secret', config('services.oidc.client_secret')),
                    // The package expects 'base_url'; fall back to 'oidc_issuer' for installs that saved before the rename
                    'services.oidc.base_url' => SystemSetting::get('oidc_base_url')
                        ?? SystemSetting::get('oidc_issuer')
                        ?? config('services.oidc.base_url'),
                    'services.oidc.scopes' => SystemSetting::get('oidc_scopes', config('services.oidc.scopes')),
                ]);
            }
        }
    }
}
