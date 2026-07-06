<?php declare(strict_types=1);

namespace Omeka\Stdlib;

/**
 * Minimal symmetric encryption for secrets stored in the database.
 *
 * A dedicated encryption subkey is derived from each configured master key to
 * serve various purposes without reusing the master key.
 *
 * Multiple keys support rotation: values are encrypted with the current (first)
 * key, and decrypted by trying each key in turn (current, then previous ones),
 * so old values stay readable until they are re-encrypted.
 *
 * When no valid key is configured, encryption is disabled and values are kept
 * in clear (opt-in and backward compatible via the prefix `sodium:`).
 */
final class Cipher
{
    const PREFIX = 'sodium:';

    /**
     * @var string[] Encryption subkeys (32 bytes); the first is the current one
     * used to encrypt.
     */
    private $keys = [];

    /**
     * @param string[]|string|null $masterKeys One or more base64 master keys.
     */
    public function __construct($masterKeys)
    {
        foreach ((array) $masterKeys as $base64MasterKey) {
            $master = is_string($base64MasterKey) && $base64MasterKey !== ''
                ? base64_decode($base64MasterKey, true)
                : false;
            if ($master !== false && strlen($master) >= SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                $this->keys[] = hash_hkdf('sha256', $master, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, 'omeka:cipher:v1');
            }
        }
    }

    public function isEnabled(): bool
    {
        return $this->keys !== [];
    }

    /**
     * Get encrypted value, or unchanged if empty, already encrypted, or no key.
     */
    public function encrypt(string $value): string
    {
        if ($value === '' || $this->keys === [] || strncmp($value, self::PREFIX, strlen(self::PREFIX)) === 0) {
            return $value;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return self::PREFIX . base64_encode($nonce . sodium_crypto_secretbox($value, $nonce, $this->keys[0]));
    }

    /**
     * Decrypt a value, or return it unchanged when it is a legacy clear value.
     *
     * @return string The decrypted value, or an empty string for fail, when the
     * value is encrypted but cannot be decrypted with any key.
     */
    public function decrypt(string $value): string
    {
        if (strncmp($value, self::PREFIX, strlen(self::PREFIX)) !== 0) {
            return $value;
        }

        if ($this->keys === []) {
            return '';
        }

        $raw = base64_decode(substr($value, strlen(self::PREFIX)), true);
        $min = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
        if ($raw === false || strlen($raw) < $min) {
            return '';
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        foreach ($this->keys as $key) {
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            if ($plain !== false) {
                return $plain;
            }
        }

        return '';
    }
}
