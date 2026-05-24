import { deriveVaultKey, importVaultKey, decryptPrivkey, storePrivkey } from './vault.js'

document.addEventListener('DOMContentLoaded', async () => {
    const pending = sessionStorage.getItem('vault_pending')
    if (!pending) return

    const submitBtn = document.querySelector('[type=submit]')
    if (submitBtn) submitBtn.disabled = true

    try {
        const { encrypted_privkey, vault_key_hex, salt, password } = JSON.parse(pending)

        // Remove immediately so the key is not kept in sessionStorage longer than needed.
        sessionStorage.removeItem('vault_pending')

        let vaultKey
        if (vault_key_hex) {
            // v2 path: vault key was already exported to hex by login.js.
            vaultKey = await importVaultKey(vault_key_hex)
        } else {
            // v1 legacy fallback: re-derive from password + salt.
            vaultKey = await deriveVaultKey(password, salt)
        }

        const privkeyPem = await decryptPrivkey(encrypted_privkey, vaultKey)
        storePrivkey(privkeyPem)
    } finally {
        if (submitBtn) submitBtn.disabled = false
    }
})
