<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'auth_method' => SystemSetting::get('auth_method', 'internal'),
            'env_ldap_override' => (bool) config('ldap.env_enabled'),
            'ldap_server' => SystemSetting::get('ldap_server', config('ldap.server')),
            'ldap_domain' => SystemSetting::get('ldap_domain', config('ldap.domain')),
            'ldap_base_dn' => SystemSetting::get('ldap_base_dn', config('ldap.basedn')),
            'ldap_use_openldap' => (bool) SystemSetting::get('ldap_use_openldap', config('ldap.openldap')),
            'ldap_trust_certificate' => (bool) SystemSetting::get('ldap_trust_certificate', false),
            'ldap_certificate' => SystemSetting::get('ldap_certificate'),
            'oidc_base_url' => SystemSetting::get('oidc_base_url') ?? SystemSetting::get('oidc_issuer'),
            'oidc_client_id' => SystemSetting::get('oidc_client_id'),
            'has_oidc_client_secret' => !blank(SystemSetting::get('oidc_client_secret')),
            'oidc_scopes' => SystemSetting::get('oidc_scopes', 'openid email profile'),
        ];

        return view('admin.settings.auth', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auth_method' => ['required', 'in:internal,ldap,oidc'],
            'ldap_server' => ['nullable', 'string', 'max:255'],
            'ldap_domain' => ['nullable', 'string', 'max:255'],
            'ldap_base_dn' => ['nullable', 'string', 'max:500'],
            'ldap_use_openldap' => ['nullable', 'boolean'],
            'ldap_trust_certificate' => ['nullable', 'boolean'],
            'ldap_certificate' => ['nullable', 'string'],
            'oidc_base_url' => ['nullable', 'url'],
            'oidc_client_id' => ['nullable', 'string', 'max:255'],
            'oidc_client_secret' => ['nullable', 'string', 'max:500'],
            'oidc_scopes' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'oidc_client_secret' && blank($value)) {
                continue;
            }

            SystemSetting::set($key, $value ?? '');
        }

        return redirect()->back()->with('success', 'Authentication settings saved.');
    }
}
