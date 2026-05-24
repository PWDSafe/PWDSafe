<template>
    <div class="max-w-sm px-12 py-10 mx-auto shadow-md border bg-white dark:bg-gray-700 dark:border-gray-700 rounded-md">
        <h3 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-200">Set your safe password</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Your safe password encrypts your vault. It is never sent to the server.
        </p>

        <div v-if="statusMessage" class="mb-4 text-sm text-gray-600 dark:text-gray-300 italic">
            {{ statusMessage }}
        </div>

        <pwdsafe-alert v-if="errorMessage" theme="danger" classes="mb-4">
            {{ errorMessage }}
        </pwdsafe-alert>

        <form @submit.prevent="handleSubmit">
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="safe_password">
                    Safe password
                </label>
                <pwdsafe-input
                    type="password"
                    id="safe_password"
                    v-model="safePassword"
                    autocomplete="new-password"
                    required
                    autofocus
                ></pwdsafe-input>
                <p v-if="updateLoginHash" class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    This will also be your new login password.
                </p>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="safe_password_confirm">
                    Confirm
                </label>
                <pwdsafe-input
                    type="password"
                    id="safe_password_confirm"
                    v-model="safePasswordConfirm"
                    autocomplete="new-password"
                    required
                ></pwdsafe-input>
            </div>
            <pwdsafe-button type="submit" class="w-full" :disabled="submitting">
                {{ submitting ? 'Setting up…' : 'Set up safe' }}
            </pwdsafe-button>
        </form>
    </div>
</template>

<script>
import { deriveVaultKey, deriveLoginHash, decryptPrivkey, encryptPrivkey, importVaultKey } from '../vault.js'
import forge from 'node-forge'

export default {
    name: 'VaultSetup',

    data() {
        const appEl = document.getElementById('vault-setup-app')
        return {
            safePassword: '',
            safePasswordConfirm: '',
            submitting: false,
            statusMessage: '',
            errorMessage: '',
            updateLoginHash: !!appEl?.dataset.updateLoginHash,
        }
    },

    methods: {
        async handleSubmit() {
            this.errorMessage = ''

            if (this.safePassword !== this.safePasswordConfirm) {
                this.errorMessage = 'Passwords do not match.'
                return
            }

            this.submitting = true

            try {
                const appEl = document.getElementById('vault-setup-app')
                const csrfToken = appEl.dataset.csrf
                const pendingData = JSON.parse(sessionStorage.getItem('vault_pending') || '{}')
                const existingVaultKeyHex = appEl.dataset.vaultKeyHex || pendingData.vault_key_hex || null
                const existingEncryptedPrivkey = appEl.dataset.encryptedPrivkey || null
                const existingVaultSalt = appEl.dataset.vaultSalt || null
                const existingPubkey = appEl.dataset.pubkey || null
                const updateLoginHash = !!appEl.dataset.updateLoginHash

                const newVaultSalt = Array.from(crypto.getRandomValues(new Uint8Array(32)))
                    .map((b) => b.toString(16).padStart(2, '0'))
                    .join('')
                const newVaultKey = await deriveVaultKey(this.safePassword, newVaultSalt)

                let privkeyPem, pubkeyPem

                if (existingVaultKeyHex && existingEncryptedPrivkey) {
                    // Migration: re-encrypt the existing private key with the new vault key.
                    this.statusMessage = 'Re-encrypting your vault…'
                    const oldVaultKey = await importVaultKey(existingVaultKeyHex)
                    privkeyPem = await decryptPrivkey(existingEncryptedPrivkey, oldVaultKey)
                    pubkeyPem = existingPubkey
                } else {
                    // New LDAP user: generate a fresh RSA-4096 key pair.
                    this.statusMessage = 'Generating keys…'
                    const keypair = await new Promise((resolve, reject) => {
                        forge.pki.rsa.generateKeyPair({ bits: 4096, workers: -1 }, (err, kp) => {
                            if (err) reject(err)
                            else resolve(kp)
                        })
                    })
                    privkeyPem = forge.pki.privateKeyToPem(keypair.privateKey)
                    pubkeyPem = forge.pki.publicKeyToPem(keypair.publicKey)
                }

                this.statusMessage = 'Saving…'
                const encryptedPrivkey = await encryptPrivkey(privkeyPem, newVaultKey)

                const payload = {
                    encrypted_privkey: encryptedPrivkey,
                    vault_salt: newVaultSalt,
                    pubkey: pubkeyPem,
                }

                // For admin-created local accounts, the vault password replaces the temporary
                // admin password as the login credential.
                if (updateLoginHash) {
                    payload.login_hash = await deriveLoginHash(newVaultKey, this.safePassword)
                }

                const response = await fetch('/api/vault/setup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                })

                const data = await response.json()
                if (!response.ok) {
                    throw new Error(data.message || 'Setup failed.')
                }

                // Store privkey in sessionStorage so the app works immediately.
                sessionStorage.setItem('vault_privkey', privkeyPem)
                window.location.href = data.redirect
            } catch (err) {
                this.errorMessage = err.message || 'An error occurred.'
                this.submitting = false
                this.statusMessage = ''
            }
        },
    },
}
</script>
