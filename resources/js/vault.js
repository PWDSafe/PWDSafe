import forge from 'node-forge'

const PBKDF2_ITERATIONS = 600_000
const PBKDF2_HASH = 'SHA-256'
const KEY_LENGTH = 256

/**
 * Derive a 256-bit AES-GCM vault key from a password and a hex-encoded salt.
 * @param {string} password
 * @param {string} saltHex  32-byte salt as a 64-character hex string
 * @returns {Promise<CryptoKey>}
 */
export async function deriveVaultKey(password, saltHex) {
    const enc = new TextEncoder()
    const salt = hexToBytes(saltHex)

    const baseKey = await crypto.subtle.importKey('raw', enc.encode(password), 'PBKDF2', false, [
        'deriveKey',
    ])

    return crypto.subtle.deriveKey(
        { name: 'PBKDF2', salt, iterations: PBKDF2_ITERATIONS, hash: PBKDF2_HASH },
        baseKey,
        { name: 'AES-GCM', length: KEY_LENGTH },
        true, // extractable so deriveLoginHash can export the raw bytes as PBKDF2 input
        ['encrypt', 'decrypt'],
    )
}

/**
 * Derive a login hash from the vault key and the raw password.
 * This is what the client sends to the server instead of the raw password:
 *   login_hash = PBKDF2-SHA256(key=vault_key, salt=password, iterations=1)
 *
 * The server can verify it with bcrypt(login_hash) but cannot reverse it to vault_key.
 *
 * @param {CryptoKey} vaultKey
 * @param {string} password  The user's raw password
 * @returns {Promise<string>}  64-character hex string
 */
export async function deriveLoginHash(vaultKey, password) {
    const exported = await crypto.subtle.exportKey('raw', vaultKey)
    const baseKey = await crypto.subtle.importKey('raw', exported, 'PBKDF2', false, ['deriveBits'])
    const bits = await crypto.subtle.deriveBits(
        { name: 'PBKDF2', salt: new TextEncoder().encode(password), iterations: 1, hash: 'SHA-256' },
        baseKey,
        256,
    )
    return Array.from(new Uint8Array(bits))
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('')
}

/**
 * Derive a login hash independently from the vault (for separate-password mode).
 * login_hash = PBKDF2-SHA256(loginPassword, login_salt, 600k) → 64-char hex
 * The login_salt is independent of the vault salt so the two passwords are fully decoupled.
 *
 * @param {string} loginPassword
 * @param {string} loginSaltHex  32-byte salt as a 64-character hex string
 * @returns {Promise<string>}  64-character hex string
 */
export async function deriveLoginHashIndependent(loginPassword, loginSaltHex) {
    const enc = new TextEncoder()
    const salt = hexToBytes(loginSaltHex)

    const baseKey = await crypto.subtle.importKey('raw', enc.encode(loginPassword), 'PBKDF2', false, [
        'deriveBits',
    ])

    const bits = await crypto.subtle.deriveBits(
        { name: 'PBKDF2', salt, iterations: PBKDF2_ITERATIONS, hash: PBKDF2_HASH },
        baseKey,
        256,
    )

    return Array.from(new Uint8Array(bits))
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('')
}

/**
 * Import a hex-encoded 32-byte AES-GCM key (e.g. from sessionStorage during 2FA flow).
 *
 * @param {string} hexKey  64-character hex string
 * @returns {Promise<CryptoKey>}
 */
export async function importVaultKey(hexKey) {
    const bytes = new Uint8Array(hexKey.match(/.{2}/g).map((b) => parseInt(b, 16)))
    return crypto.subtle.importKey('raw', bytes, { name: 'AES-GCM' }, false, ['encrypt', 'decrypt'])
}

/**
 * Decrypt a private key encrypted by Encryption::encV2() (AES-256-GCM).
 * Stored format: base64( iv[12] || ciphertext[n] || tag[16] )
 *
 * @param {string} encryptedB64  Base64-encoded encrypted private key
 * @param {CryptoKey} vaultKey
 * @returns {Promise<string>}  PEM-encoded RSA private key
 */
export async function decryptPrivkey(encryptedB64, vaultKey) {
    const raw = base64ToBytes(encryptedB64)
    const iv = raw.slice(0, 12)
    const ciphertextWithTag = raw.slice(12) // Web Crypto expects tag appended to ciphertext

    try {
        const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, vaultKey, ciphertextWithTag)
        return new TextDecoder().decode(decrypted)
    } catch {
        throw new Error('Could not decrypt vault. Incorrect password?')
    }
}

/**
 * Encrypt a PEM private key with AES-256-GCM.
 * Returns base64( iv[12] || ciphertext[n] || tag[16] ) matching Encryption::encV2().
 *
 * @param {string} privkeyPem
 * @param {CryptoKey} vaultKey
 * @returns {Promise<string>}  Base64-encoded encrypted private key
 */
export async function encryptPrivkey(privkeyPem, vaultKey) {
    const iv = crypto.getRandomValues(new Uint8Array(12))
    const enc = new TextEncoder()
    const ciphertextWithTag = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, vaultKey, enc.encode(privkeyPem))

    const result = new Uint8Array(12 + ciphertextWithTag.byteLength)
    result.set(iv, 0)
    result.set(new Uint8Array(ciphertextWithTag), 12)

    return bytesToBase64(result)
}

/**
 * Decrypt a private key encrypted with the legacy AES-256-CBC format
 * produced by PHP's openssl_encrypt($data, 'aes256', $rawPassword).
 * Stored format: base64(ciphertext) + ':' + hexIv
 *
 * Used only during migration from legacy to v2.
 *
 * @param {string} encrypted  'base64ciphertext:hexIv'
 * @param {string} password   The user's raw password (NOT the derived vault key)
 * @returns {Promise<string>}
 */
export async function decryptLegacyPrivkey(encrypted, password) {
    const [base64Ciphertext, hexIv] = encrypted.split(':')
    const ciphertext = base64ToBytes(base64Ciphertext)
    const iv = hexToBytes(hexIv)

    // PHP uses the raw password string bytes as the AES key, padded/truncated to 32 bytes.
    const enc = new TextEncoder()
    const passwordBytes = enc.encode(password)
    const keyBytes = new Uint8Array(32)
    keyBytes.set(passwordBytes.slice(0, 32))

    const key = await crypto.subtle.importKey('raw', keyBytes, { name: 'AES-CBC' }, false, ['decrypt'])

    const decrypted = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, key, ciphertext)

    return new TextDecoder().decode(decrypted)
}

/** Store the decrypted RSA private key PEM in sessionStorage for the current browser tab. */
export function storePrivkey(privkeyPem) {
    sessionStorage.setItem('vault_privkey', privkeyPem)
}

/** Retrieve the cached decrypted RSA private key PEM, or null if not available. */
export function loadPrivkey() {
    return sessionStorage.getItem('vault_privkey')
}

/** Clear the cached private key (e.g. on logout). */
export function clearPrivkey() {
    sessionStorage.removeItem('vault_privkey')
}

/**
 * Encrypt plaintext using hybrid RSA-OAEP-SHA256 + AES-256-GCM (v2 format).
 * Format: v2:<base64(iv[12]+aes_gcm_data)>:<base64(rsa_oaep_encrypted_aes_key)>
 *
 * @param {string} plaintext
 * @param {string} pubkeyPem  PEM-encoded RSA public key
 * @returns {Promise<string>}
 */
export async function encryptCredentialV2(plaintext, pubkeyPem) {
    const aesKey = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt'])
    const rawAesKey = await crypto.subtle.exportKey('raw', aesKey)

    const iv = crypto.getRandomValues(new Uint8Array(12))
    const aesEncrypted = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        aesKey,
        new TextEncoder().encode(plaintext),
    )

    const aesData = new Uint8Array(12 + aesEncrypted.byteLength)
    aesData.set(iv, 0)
    aesData.set(new Uint8Array(aesEncrypted), 12)

    const publicKey = forge.pki.publicKeyFromPem(pubkeyPem)
    const aesKeyStr = String.fromCharCode(...new Uint8Array(rawAesKey))
    const encryptedAesKey = publicKey.encrypt(aesKeyStr, 'RSA-OAEP', {
        md: forge.md.sha256.create(),
        mgf1: { md: forge.md.sha256.create() },
    })

    return `v2:${bytesToBase64(aesData)}:${forge.util.encode64(encryptedAesKey)}`
}

/**
 * Decrypt a v2 hybrid-encrypted credential.
 *
 * @param {string} ciphertext  v2:base64:base64 format
 * @param {string} privkeyPem  PEM-encoded RSA private key
 * @returns {Promise<string>}
 */
export async function decryptCredentialV2(ciphertext, privkeyPem) {
    const parts = ciphertext.split(':')
    const aesDataB64 = parts[1]
    const encryptedAesKeyB64 = parts[2]

    const privateKey = forge.pki.privateKeyFromPem(privkeyPem)
    const rawAesKey = privateKey.decrypt(forge.util.decode64(encryptedAesKeyB64), 'RSA-OAEP', {
        md: forge.md.sha256.create(),
        mgf1: { md: forge.md.sha256.create() },
    })

    const aesKeyBytes = Uint8Array.from(rawAesKey, (c) => c.charCodeAt(0))
    const aesKey = await crypto.subtle.importKey('raw', aesKeyBytes, { name: 'AES-GCM' }, false, ['decrypt'])

    const aesData = base64ToBytes(aesDataB64)
    const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: aesData.slice(0, 12) }, aesKey, aesData.slice(12))

    return new TextDecoder().decode(decrypted)
}

/**
 * Decrypt a credential — dispatches to v1 (PKCS1-v1.5) or v2 (hybrid RSA-OAEP+AES-GCM).
 *
 * @param {string} encryptedData  Ciphertext string from server
 * @param {string} privkeyPem     PEM-encoded RSA private key from sessionStorage
 * @returns {Promise<string>}
 */
export async function decryptCredential(encryptedData, privkeyPem) {
    if (encryptedData.startsWith('v2:')) {
        return decryptCredentialV2(encryptedData, privkeyPem)
    }

    // v1: RSA-PKCS1-v1.5 (chunked with '-' prefix per chunk, or single base64)
    const privateKey = forge.pki.privateKeyFromPem(privkeyPem)
    if (encryptedData.includes('-')) {
        return encryptedData
            .split('-')
            .filter((chunk) => chunk.length > 0)
            .map((chunk) => privateKey.decrypt(forge.util.decode64(chunk), 'RSAES-PKCS1-V1_5'))
            .join('')
    }
    return privateKey.decrypt(forge.util.decode64(encryptedData), 'RSAES-PKCS1-V1_5')
}

/**
 * Encrypt plaintext using the same AES-256-CBC format as PHP's Encryption::enc().
 * The token (a hex string) is used as the raw key (first 32 bytes).
 * Returns "base64(ciphertext):hex(iv)" — compatible with PHP Encryption::dec().
 *
 * @param {string} plaintext
 * @param {string} token  Hex string used as the AES key
 * @returns {Promise<string>}
 */
export async function encryptWithToken(plaintext, token) {
    const keyBytes = new Uint8Array(32)
    const tokenBytes = new TextEncoder().encode(token)
    keyBytes.set(tokenBytes.slice(0, 32))

    const iv = crypto.getRandomValues(new Uint8Array(16))
    const key = await crypto.subtle.importKey('raw', keyBytes, 'AES-CBC', false, ['encrypt'])
    const encrypted = await crypto.subtle.encrypt({ name: 'AES-CBC', iv }, key, new TextEncoder().encode(plaintext))

    const hexIv = Array.from(iv)
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('')

    return `${bytesToBase64(new Uint8Array(encrypted))}:${hexIv}`
}

/** Generate a random 40-character hex token (same length as SHA-1). */
export function generateShareToken() {
    return Array.from(crypto.getRandomValues(new Uint8Array(20)))
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('')
}

// --- Helpers ---

function hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2)
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.slice(i, i + 2), 16)
    }
    return bytes
}

function base64ToBytes(b64) {
    const binary = atob(b64)
    const bytes = new Uint8Array(binary.length)
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i)
    }
    return bytes
}

function bytesToBase64(bytes) {
    let binary = ''
    for (const byte of bytes) {
        binary += String.fromCharCode(byte)
    }
    return btoa(binary)
}
