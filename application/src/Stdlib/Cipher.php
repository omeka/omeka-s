<?php declare(strict_types=1);

namespace Omeka\Stdlib;

/**
 * Minimal symmetric encryption for secrets stored in the database.
 *
 * A dedicated encryption subkey is derived from the configured master key to
 * serve various purposes without reusing master key.
 *
 * When no valid key is configured, encryption is disabled and values are kept
 * in clear (opt-in and backward compatible via the prefix `sodium:`).
 */
final class Cipher
{
    const PREFIX = 'sodium:';

    /**
     * @var string|null Encryption subkey (32 bytes), or null when disabled.
     */
    private $key;

    public function __construct(?string $base64MasterKey)
    {
        $master = $base64MasterKey ? base64_decode($base64MasterKey, true) : false;
        $this->key = ($master !== false && strlen($master) >= SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
            ? hash_hkdf('sha256', $master, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, 'omeka:cipher:v1')
            : null;
    }

    public function isEnabled(): bool
    {
        return $this->key !== null;
    }

    /**
     * Get encrypted value, or unchanged if empty, already encrypted, or no key.
     */
    public function encrypt(string $value): string
    {
        if ($value === '' || $this->key === null || strncmp($value, self::PREFIX, strlen(self::PREFIX)) === 0) {
            return $value;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return self::PREFIX . base64_encode($nonce . sodium_crypto_secretbox($value, $nonce, $this->key));
    }

    /**
     * Decrypt a value, or return it unchanged when it is a legacy clear value.
     *
     * @return string The decrypted value, or an empty string for fail, when the
     * value is encrypted but cannot be decrypted.
     */
    public function decrypt(string $value): string
    {
        if (strncmp($value, self::PREFIX, strlen(self::PREFIX)) !== 0) {
            return $value;
        }

        if ($this->key === null) {
            return '';
        }

        $raw = base64_decode(substr($value, strlen(self::PREFIX)), true);
        $min = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
        if ($raw === false || strlen($raw) < $min) {
            return '';
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->key);
        return $plain === false ? '' : $plain;
    }
}
