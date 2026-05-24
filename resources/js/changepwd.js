import { deriveVaultKey, deriveLoginHash, deriveLoginHashIndependent, encryptPrivkey, loadPrivkey } from './vault.js'

function addHidden(form, name, value) {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = name
    input.value = value
    form.appendChild(input)
}

function showPrivkeyWarning(form) {
    const submitBtn = form.querySelector('[type=submit]')
    if (submitBtn) {
        submitBtn.disabled = true
    }
    const warning = document.createElement('p')
    warning.className = 'text-red-500 text-sm mt-2'
    warning.textContent =
        'Your vault key is not available. Please navigate to this page from the main app rather than reloading or opening directly.'
    submitBtn?.parentElement?.insertBefore(warning, submitBtn)
}

document.addEventListener('DOMContentLoaded', () => {
    // Section A: Login password change (local users, separate_vault_password mode or first separation)
    const loginForm = document.querySelector('form[data-login-change]')
    if (loginForm) {
        const isSeparate = loginForm.dataset.separate === 'true'
        const loginSalt = loginForm.dataset.loginSalt || null
        const vaultSalt = loginForm.dataset.vaultSalt

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault()

            const oldpwdEl = loginForm.querySelector('[name=oldpwd]')
            const passwordEl = loginForm.querySelector('[name=password]')
            const confirmEl = loginForm.querySelector('[name=password_confirmation]')

            const oldPassword = oldpwdEl.value
            const newPassword = passwordEl.value

            let oldLoginHash, newLoginHash

            if (isSeparate && loginSalt) {
                // Already separate — derive both hashes from login_salt.
                oldLoginHash = await deriveLoginHashIndependent(oldPassword, loginSalt)
                // New login hash will use a fresh login_salt generated server-side after verification.
                // For now send oldLoginHash for verification and newLoginHash computed from same salt.
                // The server will generate a new login_salt on store.
                const newLoginSalt = Array.from(crypto.getRandomValues(new Uint8Array(32)))
                    .map((b) => b.toString(16).padStart(2, '0'))
                    .join('')
                newLoginHash = await deriveLoginHashIndependent(newPassword, newLoginSalt)
                addHidden(loginForm, 'new_login_salt', newLoginSalt)
            } else {
                // Same password as vault — derive via vault path.
                const oldVaultKey = await deriveVaultKey(oldPassword, vaultSalt)
                oldLoginHash = await deriveLoginHash(oldVaultKey, oldPassword)
                const newLoginSalt = Array.from(crypto.getRandomValues(new Uint8Array(32)))
                    .map((b) => b.toString(16).padStart(2, '0'))
                    .join('')
                newLoginHash = await deriveLoginHashIndependent(newPassword, newLoginSalt)
                addHidden(loginForm, 'new_login_salt', newLoginSalt)
            }

            oldpwdEl.value = oldLoginHash
            passwordEl.value = newLoginHash
            if (confirmEl) confirmEl.value = newLoginHash

            loginForm.submit()
        })
    }

    // Section B: Vault (safe) password change — handles both same-password and separate modes.
    const vaultForm = document.querySelector('form[data-vault-change]')
    if (!vaultForm) return

    const isSeparateVault = vaultForm.dataset.separate === 'true'

    const privkeyPem = loadPrivkey()
    if (!privkeyPem) {
        showPrivkeyWarning(vaultForm)
        return
    }

    vaultForm.addEventListener('submit', async (e) => {
        e.preventDefault()

        const privkey = loadPrivkey()
        if (!privkey) return

        const oldpwdEl = vaultForm.querySelector('[name=oldpwd]')
        const passwordEl = vaultForm.querySelector('[name=password]')
        const confirmEl = vaultForm.querySelector('[name=password_confirmation]')

        const oldPassword = oldpwdEl.value
        const newPassword = passwordEl.value
        const currentVaultSalt = vaultForm.dataset.vaultSalt

        const newVaultSalt = Array.from(crypto.getRandomValues(new Uint8Array(32)))
            .map((b) => b.toString(16).padStart(2, '0'))
            .join('')
        const newVaultKey = await deriveVaultKey(newPassword, newVaultSalt)
        const newEncryptedPrivkey = await encryptPrivkey(privkey, newVaultKey)

        if (isSeparateVault) {
            // Flow B: already separate — server verifies by decrypting current privkey with vault_key_hex.
            const oldVaultKey = await deriveVaultKey(oldPassword, currentVaultSalt)
            const oldVaultKeyBytes = await crypto.subtle.exportKey('raw', oldVaultKey)
            const oldVaultKeyHex = Array.from(new Uint8Array(oldVaultKeyBytes))
                .map((b) => b.toString(16).padStart(2, '0'))
                .join('')

            oldpwdEl.value = oldVaultKeyHex
            // password and password_confirmation are not used server-side for separate vault change.
            passwordEl.value = newPassword
            if (confirmEl) confirmEl.value = newPassword
        } else {
            // Flow A: first separation — verify via current login hash (login_pwd = old safe pwd).
            // Also set up independent login_salt so login and vault are fully decoupled.
            const oldVaultKey = await deriveVaultKey(oldPassword, currentVaultSalt)
            const oldLoginHash = await deriveLoginHash(oldVaultKey, oldPassword)

            const newLoginSalt = Array.from(crypto.getRandomValues(new Uint8Array(32)))
                .map((b) => b.toString(16).padStart(2, '0'))
                .join('')
            // Login password stays the same as old safe password (login_pwd = old_safe_pwd before separation).
            const newLoginHash = await deriveLoginHashIndependent(oldPassword, newLoginSalt)

            oldpwdEl.value = oldLoginHash
            passwordEl.value = newLoginHash
            if (confirmEl) confirmEl.value = newLoginHash
            addHidden(vaultForm, 'new_login_salt', newLoginSalt)
        }

        addHidden(vaultForm, 'new_encrypted_privkey', newEncryptedPrivkey)
        addHidden(vaultForm, 'new_salt', newVaultSalt)

        vaultForm.submit()
    })
})
