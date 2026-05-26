<template>
    <form method="POST" action="/admin/settings/auth">
        <input type="hidden" name="_token" :value="csrf" />

        <div
            v-if="settings.env_ldap_override"
            class="mb-6 max-w-2xl rounded border border-yellow-400 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-300"
        >
            <p class="font-semibold">Environment variable override active</p>
            <p class="mt-1">
                The <code>USE_LDAP=true</code> environment variable is set, which forces LDAP
                authentication regardless of the setting below. To manage authentication from this
                interface, remove <code>USE_LDAP</code> from your environment and restart the
                application.
            </p>
        </div>

        <div class="mb-6 max-w-2xl rounded bg-white p-4 shadow-md dark:bg-gray-700">
            <h2 class="mb-4 font-semibold">Authentication method</h2>
            <div class="space-y-3">
                <label class="flex cursor-pointer items-center gap-3">
                    <input
                        type="radio"
                        name="auth_method"
                        value="internal"
                        v-model="authMethod"
                        class="h-4 w-4 text-blue-600"
                    />
                    <div>
                        <span class="font-medium text-gray-800 dark:text-gray-200">Internal</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Users log in with locally stored credentials.
                        </p>
                    </div>
                </label>
                <label class="flex cursor-pointer items-center gap-3">
                    <input
                        type="radio"
                        name="auth_method"
                        value="ldap"
                        v-model="authMethod"
                        class="h-4 w-4 text-blue-600"
                    />
                    <div>
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            LDAP / Active Directory
                        </span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Authenticate users against an LDAP or Active Directory server.
                        </p>
                    </div>
                </label>
                <label class="flex cursor-pointer items-center gap-3">
                    <input
                        type="radio"
                        name="auth_method"
                        value="oidc"
                        v-model="authMethod"
                        class="h-4 w-4 text-blue-600"
                    />
                    <div>
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            OIDC / OAuth2
                        </span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Single sign-on via an OpenID Connect provider (e.g. Azure AD, Keycloak,
                            Okta).
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <div
            v-if="authMethod === 'ldap'"
            class="mb-6 max-w-2xl rounded bg-white p-4 shadow-md dark:bg-gray-700"
        >
            <h2 class="mb-4 font-semibold">LDAP / Active Directory settings</h2>
            <div class="space-y-4">
                <div>
                    <pwdsafe-label for="ldap_server">Server URL</pwdsafe-label>
                    <pwdsafe-input
                        id="ldap_server"
                        name="ldap_server"
                        type="text"
                        placeholder="ldaps://dc.example.com"
                        :value="form.ldap_server"
                    ></pwdsafe-input>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Use <code>ldap://</code> or <code>ldaps://</code> prefix.
                    </p>
                </div>
                <div>
                    <pwdsafe-label for="ldap_domain">Domain</pwdsafe-label>
                    <pwdsafe-input
                        id="ldap_domain"
                        name="ldap_domain"
                        type="text"
                        placeholder="example.com"
                        :value="form.ldap_domain"
                    ></pwdsafe-input>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Used to construct the UPN: <code>user@domain</code>.
                    </p>
                </div>
                <div>
                    <pwdsafe-label for="ldap_base_dn">Base DN</pwdsafe-label>
                    <pwdsafe-input
                        id="ldap_base_dn"
                        name="ldap_base_dn"
                        type="text"
                        placeholder="CN=Users,DC=example,DC=com"
                        :value="form.ldap_base_dn"
                    ></pwdsafe-input>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        The base distinguished name for user searches.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <input
                        type="hidden"
                        name="ldap_use_openldap"
                        value="0"
                    />
                    <input
                        id="ldap_use_openldap"
                        name="ldap_use_openldap"
                        type="checkbox"
                        value="1"
                        :checked="form.ldap_use_openldap"
                        class="h-4 w-4 rounded text-blue-600"
                    />
                    <div>
                        <pwdsafe-label for="ldap_use_openldap" class="mb-0">
                            Use OpenLDAP mode
                        </pwdsafe-label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Binds as <code>cn=user,basedn</code> instead of
                            <code>user@domain</code>.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <input
                        type="hidden"
                        name="ldap_trust_certificate"
                        value="0"
                    />
                    <input
                        id="ldap_trust_certificate"
                        name="ldap_trust_certificate"
                        type="checkbox"
                        value="1"
                        :checked="form.ldap_trust_certificate"
                        class="h-4 w-4 rounded text-blue-600"
                    />
                    <div>
                        <pwdsafe-label for="ldap_trust_certificate" class="mb-0">
                            Trust any TLS certificate
                        </pwdsafe-label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Accept self-signed or untrusted certificates. Not recommended in
                            production.
                        </p>
                    </div>
                </div>
                <div>
                    <pwdsafe-label for="ldap_certificate">Custom CA certificate (PEM)</pwdsafe-label>
                    <textarea
                        id="ldap_certificate"
                        name="ldap_certificate"
                        rows="6"
                        placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"
                        class="block w-full rounded border border-gray-300 bg-white px-3 py-2 font-mono text-xs text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    >{{ form.ldap_certificate }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Optional. Paste a PEM-encoded CA certificate to verify the LDAP server's
                        TLS certificate.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="authMethod === 'oidc'"
            class="mb-6 max-w-2xl rounded bg-white p-4 shadow-md dark:bg-gray-700"
        >
            <h2 class="mb-4 font-semibold">OIDC / OAuth2 settings</h2>
            <div class="space-y-4">
                <div>
                    <pwdsafe-label for="oidc_base_url">Base URL</pwdsafe-label>
                    <pwdsafe-input
                        id="oidc_base_url"
                        name="oidc_base_url"
                        type="url"
                        placeholder="https://login.example.com/realms/myrealm"
                        :value="form.oidc_base_url"
                    ></pwdsafe-input>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        The URL of your OIDC provider, <strong>without</strong>
                        <code>/.well-known/openid-configuration</code>. That path is appended
                        automatically.
                    </p>
                </div>
                <div>
                    <pwdsafe-label for="oidc_client_id">Client ID</pwdsafe-label>
                    <pwdsafe-input
                        id="oidc_client_id"
                        name="oidc_client_id"
                        type="text"
                        placeholder="pwdsafe"
                        :value="form.oidc_client_id"
                    ></pwdsafe-input>
                </div>
                <div>
                    <pwdsafe-label for="oidc_client_secret">Client secret</pwdsafe-label>
                    <pwdsafe-input
                        id="oidc_client_secret"
                        name="oidc_client_secret"
                        type="password"
                        autocomplete="off"
                        :placeholder="form.has_oidc_client_secret ? 'Secret is set — leave blank to keep it' : 'Enter client secret'"
                    ></pwdsafe-input>
                </div>
                <div>
                    <pwdsafe-label for="oidc_scopes">Scopes</pwdsafe-label>
                    <pwdsafe-input
                        id="oidc_scopes"
                        name="oidc_scopes"
                        type="text"
                        placeholder="openid email profile"
                        :value="form.oidc_scopes"
                    ></pwdsafe-input>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Space-separated list of scopes to request. Default:
                        <code>openid email profile</code>. Use <code>openid email</code> if your
                        provider does not support the <code>profile</code> scope.
                    </p>
                </div>
                <div class="rounded border border-yellow-300 bg-yellow-50 p-3 text-sm text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300">
                    Configure your OIDC provider to allow the redirect URI:
                    <code class="break-all">{{ redirectUri }}</code>
                </div>
            </div>
        </div>

        <div class="max-w-2xl">
            <pwdsafe-button type="submit">Save settings</pwdsafe-button>
        </div>
    </form>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    csrf: {
        type: String,
        required: true,
    },
})

const authMethod = ref(props.settings.auth_method ?? 'internal')
const form = ref({ ...props.settings })

const redirectUri = computed(() => {
    return window.location.origin + '/auth/oidc/callback'
})
</script>
