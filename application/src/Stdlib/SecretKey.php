<?php declare(strict_types=1);

namespace Omeka\Stdlib;

/**
 * Resolve and persist the application secret keys used to encrypt secrets.
 *
 * The keys are kept out of the database for security and for privacy.
 *
 * The keys are resolved from:
 * 1. config/secret_key.php, a generated file returning the keys as an array;
 * 2. environment variable `OMEKA_SECRET_KEY`, for hosts where the config
 *    directory is not writeable (containers or managed hosting).
 *
 * Multiple keys enable rotation: the first is the current key (used to
 * encrypt), the next ones are previous keys kept to decrypt older values.
 *
 * The config directory is OMEKA_PATH . '/config/' and can be overridden mainly
 * for testing.
 */
final class SecretKey
{
    const FILE = 'secret_key.php';

    const ENV = 'OMEKA_SECRET_KEY';

    /**
     * Resolve the secret keys (the first is current), or an empty array.
     *
     * @return string[]
     */
    public static function resolve(?string $configDir = null): array
    {
        $file = self::filePath($configDir);
        if (is_readable($file)) {
            $keys = self::normalize(include $file);
            if ($keys !== []) {
                return $keys;
            }
        }

        return self::normalize(getenv(self::ENV));
    }

    /**
     * Generate a new secret key, a base64 string of 32 random bytes.
     */
    public static function generate(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Store a key in the config directory, as an array to allow rotation.
     *
     * @return bool False when the file could not be written.
     */
    public static function store(string $base64Key, ?string $configDir = null): bool
    {
        $file = self::filePath($configDir);
        $exportKey = var_export($base64Key, true);
        $content = <<<PHP
            <?php
            // Application secret keys.
            // The first is the current key (used to encrypt).
            // Add previous keys after it to keep decrypting old values during rotation.
            return [
                $exportKey,
            ];

            PHP;
        if (@file_put_contents($file, $content, LOCK_EX) === false) {
            return false;
        }
        @chmod($file, 0600);
        return true;
    }

    /**
     * @param string[]|string|false $value
     * @return string[]
     */
    private static function normalize($value): array
    {
        $keys = [];
        foreach ((array) $value as $key) {
            if (is_string($key) && $key !== '') {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    private static function filePath(?string $configDir): string
    {
        return ($configDir ?? OMEKA_PATH . '/config') . '/' . self::FILE;
    }
}
