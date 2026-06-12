import forge from 'node-forge'
import { deriveVaultKey, deriveLoginHash, encryptPrivkey, storePrivkey } from './vault.js'

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-register]')
    if (!form) return

    const statusEl = document.getElementById('register-status')
    const spinner = document.getElementById('register-spinner')
    const submitText = document.getElementById('register-submit-text')

    const setStatus = (msg) => {
        if (statusEl) statusEl.textContent = msg
    }

    const setLoading = (loading, submitBtn) => {
        if (submitBtn) submitBtn.disabled = loading
        if (spinner) spinner.classList.toggle('hidden', !loading)
        if (submitText) submitText.textContent = loading ? 'Registering…' : 'Register'
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault()

        const email = form.querySelector('[name=email]').value
        const password = form.querySelector('[name=password]').value
        const confirmation = form.querySelector('[name=password_confirmation]').value

        const setPasswordError = (hasError) => {
            for (const input of form.querySelectorAll('input[name=password], input[name=password_confirmation]')) {
                input.style.borderColor = hasError ? 'rgb(239, 68, 68)' : ''
            }
        }

        if (password !== confirmation) {
            setPasswordError(true)
            setStatus('Passwords do not match.')
            return
        }
        if (password.length < 8) {
            setPasswordError(true)
            setStatus('Password must be at least 8 characters.')
            return
        }

        setPasswordError(false)
        setStatus('Generating keys… this may take a few seconds.')

        const submitBtn = form.querySelector('[type=submit]')
        setLoading(true, submitBtn)

        try {
            // Generate RSA 4096 key pair in the browser — server never sees the private key.
            const keypair = await new Promise((resolve, reject) => {
                forge.pki.rsa.generateKeyPair({ bits: 4096, workers: -1 }, (err, kp) => {
                    if (err) reject(err)
                    else resolve(kp)
                })
            })
            const privkeyPem = forge.pki.privateKeyToPem(keypair.privateKey)
            const pubkeyPem = forge.pki.publicKeyToPem(keypair.publicKey)

            // Derive vault key and login hash — raw password stays in the browser.
            const salt = Array.from(crypto.getRandomValues(new Uint8Array(32)))
                .map((b) => b.toString(16).padStart(2, '0'))
                .join('')
            const vaultKey = await deriveVaultKey(password, salt)
            const loginHash = await deriveLoginHash(vaultKey, password)
            const encryptedPrivkey = await encryptPrivkey(privkeyPem, vaultKey)

            // Replace password fields with the login hash so the server only ever sees the hash.
            form.querySelector('[name=password]').value = loginHash
            form.querySelector('[name=password_confirmation]').value = loginHash

            const addHidden = (name, value) => {
                const input = document.createElement('input')
                input.type = 'hidden'
                input.name = name
                input.value = value
                form.appendChild(input)
            }

            addHidden('encrypted_privkey', encryptedPrivkey)
            addHidden('privkey_salt', salt)
            addHidden('pubkey', pubkeyPem)

            storePrivkey(privkeyPem)
            setStatus('')
            form.submit()
        } catch (err) {
            setStatus('Key generation failed. Please try again.')
            setLoading(false, submitBtn)
        }
    })
})
