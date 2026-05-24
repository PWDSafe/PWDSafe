<?php

namespace App\Helpers;

/**
 * Class Encryption Used for encrypting and decrypting data via OpenSSL
 * @package DevpeakIT\PWDSafe
 */
class Encryption
{
    private const CHUNK_SIZE = 500;

    /**
     * @return array<int, string>
     */
    public function genNewKeys(): array
    {
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];
        return [$privKey, $pubKey];
    }

    /**
     * @param $data string to encrypt
     * @param $pwd string to use as key for the encryption
     * @return string
     */
    public function enc(string $data, string $pwd): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes256"));
        $encrypted = openssl_encrypt($data, "aes256", $pwd, 0, $iv);
        return $encrypted . ":" . bin2hex($iv);
    }

    /**
     * @param $data string to decrypt
     * @param $pwd string to use as key for the decryption
     * @return string
     */
    public function dec(string $data, string $pwd): string
    {
        list($data, $biniv) = explode(":", $data);
        $iv = hex2bin($biniv);
        return openssl_decrypt($data, "aes256", $pwd, 0, $iv);
    }

    public function encWithPub(string $data, string $pubkey): string
    {
        $encrypted = '';

        if (strlen($data) > self::CHUNK_SIZE) {
            $parts = str_split($data, self::CHUNK_SIZE);
            foreach ($parts as $part) {
                openssl_public_encrypt($part, $enc, $pubkey);
                $encrypted .= '-' . base64_encode($enc);
                $enc = '';
            }
        } else {
            openssl_public_encrypt($data, $encrypted, $pubkey);
            $encrypted = base64_encode($encrypted);
        }

        return $encrypted;
    }

    /**
     * Derive a 32-byte vault key from a password and a hex-encoded salt using PBKDF2-SHA256.
     * Returns the raw (binary) key bytes.
     */
    public static function deriveVaultKey(string $password, string $saltHex): string
    {
        return hash_pbkdf2(
            'sha256',
            $password,
            hex2bin($saltHex),
            config('vault.pbkdf2_iterations'),
            32,
            true
        );
    }

    /**
     * Derive a login hash from a vault key and the raw password.
     * This is what the client sends to the server instead of the raw password,
     * so the server can never compute the vault key even if it captures the login request.
     *
     * Formula: PBKDF2-SHA256(key=vault_key, salt=password, iterations=1)
     * Returns a 64-character hex string.
     */
    public static function deriveLoginHash(string $vaultKey, string $password): string
    {
        return bin2hex(hash_pbkdf2('sha256', $vaultKey, $password, 1, 32, true));
    }

    /**
     * Derive a login hash independently from the vault (for separate-password mode).
     * Formula: PBKDF2-SHA256(password, login_salt, PBKDF2_ITERATIONS)
     * Returns a 64-character hex string.
     * Mirrors deriveLoginHashIndependent() in vault.js.
     */
    public static function deriveLoginHashIndependent(string $password, string $loginSaltHex): string
    {
        return bin2hex(hash_pbkdf2(
            'sha256',
            $password,
            hex2bin($loginSaltHex),
            config('vault.pbkdf2_iterations'),
            32,
            true
        ));
    }

    /**
     * Encrypt data using AES-256-GCM with a raw 32-byte vault key.
     * Stored format: base64( iv[12] || ciphertext[n] || tag[16] )
     */
    public function encV2(string $data, string $vaultKey): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($data, 'aes-256-gcm', $vaultKey, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        return base64_encode($iv . $ciphertext . $tag);
    }

    /**
     * Decrypt data encrypted by encV2().
     */
    public function decV2(string $encoded, string $vaultKey): string
    {
        $raw = base64_decode($encoded);
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, -16);
        $ciphertext = substr($raw, 12, strlen($raw) - 28);

        return (string) openssl_decrypt($ciphertext, 'aes-256-gcm', $vaultKey, OPENSSL_RAW_DATA, $iv, $tag);
    }

    public function decWithPriv(string $data, string $privkey): string
    {
        $decrypted = '';
        if (str_contains($data, '-')) {
            $parts = explode("-", $data);
            foreach ($parts as $part) {
                $part = base64_decode($part);
                openssl_private_decrypt($part, $dec, $privkey);
                $decrypted .= $dec;
                $dec = '';
            }
        } else {
            $data = base64_decode($data);
            openssl_private_decrypt($data, $decrypted, $privkey);
        }

        return $decrypted;
    }
}
