<template>
    <div class="max-w-sm px-12 py-10 mx-auto shadow-md border bg-white dark:bg-gray-700 dark:border-gray-700 rounded-md">
        <template v-if="!confirmingReset">
            <h3 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-200">Unlock your safe</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                <template v-if="isRecovery">
                    Your safe could not be decrypted with your new password. Enter your
                    <strong>previous safe password</strong> to recover access.
                </template>
                <template v-else>
                    Enter your safe password to decrypt your safe.
                </template>
            </p>

            <pwdsafe-alert v-if="errorMessage" theme="danger" classes="mb-4">
                {{ errorMessage }}
            </pwdsafe-alert>

            <form @submit.prevent="handleSubmit">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="safe_password">
                        Safe password
                    </label>
                    <pwdsafe-input
                        type="password"
                        id="safe_password"
                        v-model="safePassword"
                        autocomplete="current-password"
                        required
                        autofocus
                    ></pwdsafe-input>
                </div>
                <pwdsafe-button type="submit" class="w-full" :disabled="submitting">
                    {{ submitting ? 'Unlocking…' : 'Unlock safe' }}
                </pwdsafe-button>
            </form>

            <div class="mt-4 text-center">
                <button
                    type="button"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:underline"
                    @click="confirmingReset = true"
                >
                    Forgot safe password?
                </button>
            </div>
        </template>

        <template v-else>
            <h3 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-200">Reset your safe</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                This will permanently delete all your stored credentials and group memberships. You will need to set a new safe password.
            </p>

            <pwdsafe-alert theme="danger" classes="mb-4">
                This action cannot be undone.
            </pwdsafe-alert>

            <pwdsafe-alert v-if="resetError" theme="danger" classes="mb-4">
                {{ resetError }}
            </pwdsafe-alert>

            <div class="flex gap-3">
                <pwdsafe-button theme="danger" :disabled="resetting" @click="handleReset">
                    {{ resetting ? 'Resetting…' : 'Reset safe' }}
                </pwdsafe-button>
                <pwdsafe-button theme="secondary" :disabled="resetting" @click="confirmingReset = false">
                    Cancel
                </pwdsafe-button>
            </div>
        </template>
    </div>
</template>

<script>
import { deriveVaultKey, decryptPrivkey, encryptPrivkey, storePrivkey, importVaultKey } from '../vault.js'

export default {
    name: 'VaultUnlock',

    data() {
        return {
            safePassword: '',
            submitting: false,
            errorMessage: '',
            confirmingReset: false,
            resetting: false,
            resetError: '',
            isRecovery: false,
        }
    },

    mounted() {
        const pending = JSON.parse(sessionStorage.getItem('vault_pending') || '{}')
        this.isRecovery = pending.recovery === true
    },

    methods: {
        async handleSubmit() {
            this.errorMessage = ''
            this.submitting = true

            try {
                const appEl = document.getElementById('vault-unlock-app')
                const csrfToken = appEl.dataset.csrf

                const pending = JSON.parse(sessionStorage.getItem('vault_pending') || '{}')
                let { encrypted_privkey, vault_key_hex, salt, password: rawPassword, new_vault_key_hex, recovery } = pending

                // OIDC / headless login: sessionStorage not populated, fetch from API
                if (!encrypted_privkey) {
                    const resp = await fetch('/api/vault/key-data', {
                        headers: { Accept: 'application/json' },
                    })
                    if (!resp.ok) {
                        throw new Error('Failed to load safe data. Please log in again.')
                    }
                    const keyData = await resp.json()
                    encrypted_privkey = keyData.encrypted_privkey
                    salt = keyData.salt
                }

                let vaultKey
                if (vault_key_hex) {
                    // vault_key_hex was set by login (Case 2: local separate pwd, stored as hex).
                    // Re-derive from safe_password + vault salt to confirm correct password.
                    vaultKey = await deriveVaultKey(this.safePassword, salt)
                } else if (rawPassword) {
                    // LDAP fallback: derive from LDAP password stored in pending.
                    vaultKey = await deriveVaultKey(rawPassword, salt)
                } else if (salt) {
                    vaultKey = await deriveVaultKey(this.safePassword, salt)
                } else {
                    throw new Error('No vault key material found. Please log in again.')
                }

                const privkeyPem = await decryptPrivkey(encrypted_privkey, vaultKey)

                storePrivkey(privkeyPem)
                sessionStorage.removeItem('vault_pending')

                if (recovery && new_vault_key_hex) {
                    // Recovery after admin password reset: re-encrypt the private key with the
                    // new vault key (derived from the new login password) so future logins work.
                    const newVaultKey = await importVaultKey(new_vault_key_hex)
                    const reEncryptedPrivkey = await encryptPrivkey(privkeyPem, newVaultKey)

                    const recoverResp = await fetch('/api/vault/recover', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ encrypted_privkey: reEncryptedPrivkey, salt }),
                    })

                    const recoverData = await recoverResp.json()
                    if (!recoverResp.ok) {
                        throw new Error(recoverData.message || 'Failed to save recovered vault.')
                    }

                    window.location.href = recoverData.redirect
                    return
                }

                const response = await fetch('/api/vault/confirm-unlock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({}),
                })

                const data = await response.json()
                if (!response.ok) {
                    throw new Error(data.message || 'Unlock failed.')
                }

                window.location.href = data.redirect
            } catch (err) {
                this.errorMessage = err.message || 'An error occurred.'
                this.submitting = false
            }
        },

        async handleReset() {
            this.resetError = ''
            this.resetting = true

            try {
                const appEl = document.getElementById('vault-unlock-app')
                const csrfToken = appEl.dataset.csrf

                const response = await fetch('/api/vault/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({}),
                })

                const data = await response.json()
                if (!response.ok) {
                    throw new Error(data.message || 'Reset failed.')
                }

                window.location.href = data.redirect
            } catch (err) {
                this.resetError = err.message || 'An error occurred.'
                this.resetting = false
            }
        },
    },
}
</script>
