<?php declare(strict_types=1);

namespace Omeka\Stdlib;

/**
 * Resolve and persist the application secret key used to encrypt secrets.
 *
 * The key is kept out of the database for security and for privacy.
 *
 * The key is resolved from:
 * 1. config/secret_key.php, a generated file created during install;
 * 2. environment variable  `OMEKA_SECRET_KEY`, for hosts where the config
 *    directory is not writeable (containers or managed hosting).
 *
 * The config directory is OMEKA_PATH . '/config/' and can be overridden mainly
 * for testing.
 */
final class SecretKey
{
    const FILE = 'secret_key.php';

    const ENV = 'OMEKA_SECRET_KEY';

    /**
     * Resolve the secret key, or null when none is set.
     */
    public static function resolve(?string $configDir = null): ?string
    {
        $file = self::filePath($configDir);
        if (is_readable($file)) {
            $key = include $file;
            if (is_string($key) && $key !== '') {
                return $key;
            }
        }

        $env = getenv(self::ENV);
        if (is_string($env) && $env !== '') {
            return $env;
        }

        return null;
    }

    /**
     * Generate a new secret key, a base64 string of 32 random bytes.
     */
    public static function generate(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Store a generated key in the config directory.
     *
     * @return bool False when the file could not be written.
     */
    public static function store(string $base64Key, ?string $configDir = null): bool
    {
        $file = self::filePath($configDir);
        $content = "<?php\nreturn " . var_export($base64Key, true) . ";\n";
        if (@file_put_contents($file, $content, LOCK_EX) === false) {
            return false;
        }

        @chmod($file, 0600);
        return true;
    }

    private static function filePath(?string $configDir): string
    {
        return ($configDir ?? OMEKA_PATH . '/config') . '/' . self::FILE;
    }
}
