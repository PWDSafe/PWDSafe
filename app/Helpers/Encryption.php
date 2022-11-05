<?php
namespace App\Helpers;

/**
 * Class Encryption Used for encrypting and decrypting data via OpenSSL
 * @package DevpeakIT\PWDSafe
 */
class Encryption
{
    // Used for encrypting data longer than encryption key
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
