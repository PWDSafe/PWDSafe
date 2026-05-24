import { deriveVaultKey, deriveLoginHash, deriveLoginHashIndependent, decryptPrivkey, storePrivkey } from './vault.js'

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form.form-signin')
    if (!form) return

    const errorEl = document.getElementById('js-login-error')

    form.addEventListener('submit', async (e) => {
        e.preventDefault()

        const email = form.querySelector('[name=email]').value
        const password = form.querySelector('[name=password]').value
        const internalFallback = form.querySelector('[name=internal]')?.value === '1'
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content ??
            form.querySelector('[name=_token]').value

        if (errorEl) errorEl.classList.add('hidden')
        for (const input of form.querySelectorAll('input[name=email], input[name=password]')) {
            input.style.borderColor = ''
        }

        // Fetch the user's KDF parameters before authentication so we can derive the
        // vault key and login hash without ever sending the raw password to the server.
        let preData
        try {
            const pre = await fetch('/api/vault/preflight?email=' + encodeURIComponent(email))
            preData = await pre.json()
        } catch {
            // Network error — fall back to standard form submit
            form.submit()
            return
        }

        const { salt, uses_login_hash: usesLoginHash, separate_vault_password: separateVaultPassword, login_salt: loginSalt } = preData

        // Derive vault key client-side (raw password never leaves the browser after this point).
        let vaultKey, loginHash
        if (salt) {
            vaultKey = await deriveVaultKey(password, salt)
            loginHash = await deriveLoginHash(vaultKey, password)
        }

        // Build the login payload.
        const body = { email, ...(internalFallback && { internal: true }) }
        if (separateVaultPassword && loginSalt) {
            // Case 2: local user with separate vault password — derive login hash from login_salt.
            body.password = await deriveLoginHashIndependent(password, loginSalt)
        } else if (usesLoginHash && loginHash) {
            // Case 1: local user, same password (or LDAP user with configured vault using login_hash).
            body.password = loginHash
        } else {
            // v1 user (or unknown user): send raw password for auth + login_hash for migration.
            body.password = password
            if (loginHash) {
                body.login_hash = loginHash
            }
        }

        let response, data
        try {
            response = await fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            })
            data = await response.json()
        } catch {
            form.submit()
            return
        }

        if (!response.ok) {
            for (const input of form.querySelectorAll('input[name=email], input[name=password]')) {
                input.style.borderColor = 'rgb(239, 68, 68)'
            }
            if (errorEl) errorEl.classList.remove('hidden')
            return
        }

        if (data.needs_2fa) {
            // Store encrypted privkey + vault key so the OTP page can decrypt the
            // private key without needing the raw password.
            const vaultKeyHex = vaultKey
                ? Array.from(new Uint8Array(await crypto.subtle.exportKey('raw', vaultKey)))
                      .map((b) => b.toString(16).padStart(2, '0'))
                      .join('')
                : null
            sessionStorage.setItem(
                'vault_pending',
                JSON.stringify({
                    encrypted_privkey: data.vault_data.encrypted_privkey,
                    vault_key_hex: vaultKeyHex,
                    // Legacy fallback for v1 users whose vault_key was migrated server-side.
                    salt: data.vault_data.salt,
                    password: vaultKeyHex ? null : password,
                }),
            )
            window.location.href = data.redirect
            return
        }

        if (data.needs_vault_setup) {
            // Store vault_key_hex so VaultSetup can re-encrypt the existing private key
            // instead of generating a new RSA key pair and losing access to old credentials.
            // Prefer the server-provided key (LDAP path); fall back to the client-derived
            // vault key for local users whose uses_login_hash flag was set on a prior login
            // (causing the server's migration_vault_key_hex session key to be absent).
            const clientVaultKeyHex = vaultKey
                ? Array.from(new Uint8Array(await crypto.subtle.exportKey('raw', vaultKey)))
                      .map((b) => b.toString(16).padStart(2, '0'))
                      .join('')
                : null
            sessionStorage.setItem(
                'vault_pending',
                JSON.stringify({
                    encrypted_privkey: data.vault_data?.encrypted_privkey ?? null,
                    vault_key_hex: data.vault_key_hex ?? clientVaultKeyHex,
                    salt: data.vault_data?.salt ?? null,
                    pubkey: data.vault_data?.pubkey ?? null,
                }),
            )
            window.location.href = data.redirect
            return
        }

        if (data.needs_vault_unlock) {
            // Cases 2 & 3: vault unlock required. Try to auto-unlock first: if the login
            // password also decrypts the vault, skip the unlock page entirely.
            if (vaultKey && data.vault_data?.encrypted_privkey) {
                try {
                    const privkeyPem = await decryptPrivkey(data.vault_data.encrypted_privkey, vaultKey)
                    storePrivkey(privkeyPem)
                    const confirmResp = await fetch('/api/vault/confirm-unlock', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({}),
                    })
                    const confirmData = await confirmResp.json()
                    if (confirmResp.ok) {
                        window.location.href = confirmData.redirect
                        return
                    }
                } catch {
                    // Auto-unlock failed (different vault password) — fall through to unlock page.
                }
            }

            // Store vault data for the unlock page.
            const vaultKeyHex = vaultKey
                ? Array.from(new Uint8Array(await crypto.subtle.exportKey('raw', vaultKey)))
                      .map((b) => b.toString(16).padStart(2, '0'))
                      .join('')
                : null
            sessionStorage.setItem(
                'vault_pending',
                JSON.stringify({
                    encrypted_privkey: data.vault_data.encrypted_privkey,
                    vault_key_hex: vaultKeyHex,
                    salt: data.vault_data.salt,
                    pubkey: data.vault_data.pubkey,
                    // For LDAP (Case 3): raw password so vault unlock page can derive vault key.
                    password: vaultKeyHex ? null : password,
                }),
            )
            window.location.href = data.redirect
            return
        }

        // Case 1: local user, same password — decrypt privkey and go to app.
        if (data.vault_data && vaultKey) {
            const { encrypted_privkey } = data.vault_data
            try {
                const privkeyPem = await decryptPrivkey(encrypted_privkey, vaultKey)
                storePrivkey(privkeyPem)
            } catch {
                // Vault can't be decrypted with this password (e.g. admin reset the password).
                // Store the new vault key so the recovery page can re-encrypt after the user
                // enters their previous safe password.
                const newVaultKeyHex = Array.from(
                    new Uint8Array(await crypto.subtle.exportKey('raw', vaultKey)),
                )
                    .map((b) => b.toString(16).padStart(2, '0'))
                    .join('')
                sessionStorage.setItem(
                    'vault_pending',
                    JSON.stringify({
                        encrypted_privkey,
                        salt: data.vault_data.salt,
                        new_vault_key_hex: newVaultKeyHex,
                        recovery: true,
                    }),
                )
                window.location.href = '/vault/unlock'
                return
            }
        }

        window.location.href = data.redirect
    })
})
