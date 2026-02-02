<?php declare(strict_types=1);

namespace Omeka\Module;

use Laminas\Config\Reader\Ini as IniReader;

/**
 * Read module/theme info from composer.json and/or config/*.ini files.
 *
 * Priority: installed.json > composer.json > module.ini/theme.ini > defaults.
 *
 * For performance, installed.json is loaded once and cached. It contains
 * metadata for all Composer-installed packages, avoiding individual file reads.
 *
 * Note: installed.json is used instead of installed.php because installed.php
 * only contains minimal data (version, type, install_path) without the `extra`
 * keys needed for add-on metadata (omeka-version-constraint, label, etc.).
 */
class InfoReader
{
    /**
     * Cache of installed.json data, indexed by add-on directory name.
     *
     * @var array|null
     */
    protected static $installedAddons = null;

    /**
     * Mapping from composer.json to ini keys.
     *
     * @var array
     */
    protected $composerToIniMap = [
        // composer.json key => ini key
        'description' => 'description',
        'license' => 'license',
        // theme_link is managed below.
        'homepage' => 'module_link',
        'version' => 'version',
        // The mapping of key "standalone" is useless: it does not exist in ini.
    ];

    /**
     * Mapping from composer.json extra keys to ini keys.
     *
     * @var array
     */
    protected $extraToIniMap = [
        'label' => 'name',
        'addon-version' => 'version',
        'configurable' => 'configurable',
        'omeka-version-constraint' => 'omeka_version_constraint',
        'has-translations' => 'has_translations',
        'omeka-helpers' => 'helpers',
    ];

    /**
     * Read info from composer.json and ini file.
     *
     * @param string $path The module/theme directory path
     * @param string $type 'module' or 'theme'
     * @return array|null Returns merged info array, or null if no valid source
     * found.
     */
    public function read(string $path, string $type = 'module'): ?array
    {
        $composerInfo = $this->readComposerJson($path);
        $iniInfo = $this->readIniFile($path, $type);

        // At least one source must exist.
        if ($composerInfo === null && $iniInfo === null) {
            return null;
        }

        return $this->merge($composerInfo, $iniInfo, $path);
    }

    /**
     * Check if the info is valid (has required fields).
     */
    public function isValid(?array $info): bool
    {
        if (!$info) {
            return false;
        }

        // Required field: name only.
        // Version (or extra/addon-version) can be derived from composer.
        if (empty($info['name'])) {
            return false;
        }

        return true;
    }

    /**
     * Read and parse composer.json file.
     */
    protected function readComposerJson(string $path): ?array
    {
        $file = $path . '/composer.json';
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $json = json_decode($content, true);
        return is_array($json)
            ? $json
            : null;
    }

    /**
     * Read and parse ini file.
     *
     * @param string $type "module" or "theme".
     */
    protected function readIniFile(string $path, string $type): ?array
    {
        $file = $path . '/config/' . $type . '.ini';
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $iniReader = new IniReader();
        try {
            $ini = $iniReader->fromFile($file);
        } catch (\Exception $e) {
            return null;
        }

        return $ini['info'] ?? null;
    }

    /**
     * Merge composer.json and ini info, with composer.json taking precedence.
     */
    protected function merge(?array $composerJson, ?array $iniInfo, string $path): array
    {
        $info = [];

        // Start with ini info as base.
        if ($iniInfo) {
            $info = $iniInfo;
        }

        // If no composer.json, return ini info with defaults
        if (!$composerJson) {
            return $this->applyDefaults($info, $path);
        }

        // Map standard composer.json fields.
        foreach ($this->composerToIniMap as $composerKey => $iniKey) {
            if (isset($composerJson[$composerKey]) && $composerJson[$composerKey] !== '') {
                $info[$iniKey] = $composerJson[$composerKey];
            }
        }

        // Map extra fields.
        $extra = $composerJson['extra'] ?? [];
        foreach ($this->extraToIniMap as $extraKey => $iniKey) {
            if (isset($extra[$extraKey])) {
                $info[$iniKey] = $extra[$extraKey];
            }
        }

        // Map keywords to tags.
        if (isset($composerJson['keywords']) && is_array($composerJson['keywords'])) {
            // Filter out generic keywords.
            $keywords = array_filter($composerJson['keywords'], function ($keyword) {
                return !in_array(strtolower($keyword), [
                    'omeka',
                    'omeka s',
                    'omeka-s',
                    'omeka s module',
                    'omeka module',
                    'module',
                    'omeka s theme',
                    'omeka theme',
                    'theme',
                ]);
            });
            if (count($keywords)) {
                $info['tags'] = implode(', ', $keywords);
            }
        }

        // Map authors.
        if (isset($composerJson['authors']) && is_array($composerJson['authors']) && count($composerJson['authors'])) {
            $firstAuthor = $composerJson['authors'][0];
            if (isset($firstAuthor['name']) && !isset($info['author'])) {
                $info['author'] = $firstAuthor['name'];
            }
            if (isset($firstAuthor['homepage']) && !isset($info['author_link'])) {
                $info['author_link'] = $firstAuthor['homepage'];
            }
        }

        // Map support.
        if (isset($composerJson['support']) && is_array($composerJson['support'])) {
            if (isset($composerJson['support']['issues']) && !isset($info['support_link'])) {
                $info['support_link'] = $composerJson['support']['issues'];
            }
        }

        // Specific: theme_link for themes.
        if (isset($composerJson['homepage']) && !isset($info['theme_link'])) {
            $info['theme_link'] = $composerJson['homepage'];
        }

        return $this->applyDefaults($info, $path, $composerJson);
    }

    /**
     * Apply default values for missing fields.
     */
    protected function applyDefaults(array $info, string $path, ?array $composerJson = null): array
    {
        $dirName = basename($path);

        // Default name: use directory name if not set.
        if (empty($info['name'])) {
            // Try to get a nice name from composer project name
            if ($composerJson !== null && isset($composerJson['name'])) {
                $info['name'] = $this->projectNameToLabel($composerJson['name']);
            } else {
                // Convert CamelCase to "Camel Case"
                $info['name'] = preg_replace('/([a-z])([A-Z])/', '$1 $2', $dirName);
            }
        }

        // Default version: try composer installed data, then fallback.
        if (empty($info['version'])) {
            $composerVersion = $this->getVersionFromComposerInstalled($composerJson);
            if ($composerVersion) {
                $info['version'] = $composerVersion;
            } else {
                // According to composer, default version is 1.0.0.
                $info['version'] = '1.0.0';
            }
        }

        // Default configurable is false.
        $info['configurable'] = !empty($info['configurable']);

        return $info;
    }

    /**
     * Try to get version from Composer's installed.json.
     */
    protected function getVersionFromComposerInstalled(?array $composerJson): ?string
    {
        if ($composerJson === null || !isset($composerJson['name'])) {
            return null;
        }

        $packageName = $composerJson['name'];
        $installedFile = OMEKA_PATH . '/vendor/composer/installed.json';

        if (!is_file($installedFile) || !is_readable($installedFile)) {
            return null;
        }

        $content = file_get_contents($installedFile);
        $installed = json_decode($content, true);

        if (!$installed) {
            return null;
        }

        // Composer 2.x format: packages are in "packages" key.
        $packages = $installed['packages'] ?? $installed;

        foreach ($packages as $package) {
            if (isset($package['name']) && $package['name'] === $packageName) {
                $version = $package['version'] ?? null;
                if ($version) {
                    // Remove 'v' prefix if present.
                    return ltrim($version, 'vV');
                }
            }
        }

        return null;
    }

    /**
     * Get the installer name (directory name) from composer.json.
     */
    public function getInstallerName(string $path): ?string
    {
        $composerJson = $this->readComposerJson($path);
        if ($composerJson === null) {
            return null;
        }

        // First check extra.installer-name.
        if (isset($composerJson['extra']['installer-name'])) {
            return $composerJson['extra']['installer-name'];
        }

        // Fall back to computed directory name from project name.
        if (isset($composerJson['name'])) {
            return $this->projectNameToDirectory($composerJson['name']);
        }

        return null;
    }

    /**
     * Convert a composer project name to a human-readable label.
     *
     * Removes common prefixes and suffixes like AddonInstaller::inflect*.
     *
     * Example: "daniel-km/omeka-s-module-easy-admin" => "Easy Admin"
     */
    public function projectNameToLabel(string $projectName): string
    {
        // Extract composer project name.
        $parts = explode('/', $projectName);
        $project = end($parts);

        // Remove common prefixes and suffixes.
        $project = preg_replace('/^(omeka-?s?-?)?(module-|theme-)?/i', '', $project);
        $project = preg_replace('/(-module|-theme)?(-omeka-?s?)?$/i', '', $project);

        // Convert kebab-case to Title Case.
        $words = explode('-', $project);
        $words = array_map('ucfirst', $words);

        return implode(' ', $words);
    }

    /**
     * Convert a composer project name to a directory name.
     *
     * Removes common prefixes and suffixes like AddonInstaller::inflect*.
     *
     * Example: "daniel-km/omeka-s-module-easy-admin" => "EasyAdmin"
     */
    public function projectNameToDirectory(string $projectName): string
    {
        // Extract composer project name.
        $parts = explode('/', $projectName);
        $project = end($parts);

        // Remove common prefixes and suffixes.
        $project = preg_replace('/^(omeka-?s?-?)?(module-|theme-)?/i', '', $project);
        $project = preg_replace('/(-module|-theme)?(-omeka-?s?)?$/i', '', $project);

        // Convert kebab-case to PascalCase.
        $words = explode('-', $project);
        $words = array_map('ucfirst', $words);

        return implode('', $words);
    }

    /**
     * Load installed.json once and cache add-on metadata indexed by dir name.
     *
     * This avoids reading individual composer.json files for composer-installed
     * add-ons, significantly improving bootstrap performance.
     */
    public function loadComposerInstalled(): void
    {
        if (self::$installedAddons !== null) {
            return;
        }

        self::$installedAddons = ['modules' => [], 'themes' => []];

        $installedFile = OMEKA_PATH . '/vendor/composer/installed.json';
        if (!is_file($installedFile) || !is_readable($installedFile)) {
            return;
        }

        $content = file_get_contents($installedFile);
        $installed = json_decode($content, true);
        if (!$installed) {
            return;
        }

        // Composer 2.x format: packages are in "packages" key.
        $packages = $installed['packages'] ?? $installed;

        foreach ($packages as $package) {
            $type = $package['type'] ?? '';
            if ($type === 'omeka-s-module') {
                $key = 'modules';
            } elseif ($type === 'omeka-s-theme') {
                $key = 'themes';
            } else {
                continue;
            }

            $extra = $package['extra'] ?? [];

            // Determine the directory name.
            $dirName = $extra['installer-name']
                ?? $this->projectNameToDirectory($package['name'] ?? '');

            if (empty($dirName)) {
                continue;
            }

            // Build the info array from installed.json data.
            $info = $this->buildInfoFromPackage($package);

            self::$installedAddons[$key][$dirName] = $info;
        }
    }

    /**
     * Get add-on info from installed.json cache.
     *
     * @param string $addonId The add-on directory name
     * @param string $type 'module' or 'theme'
     * @return array|null The add-on info or null if not found in installed.json.
     */
    public function getFromComposerInstalled(string $addonId, string $type = 'module'): ?array
    {
        $this->loadComposerInstalled();

        $key = $type === 'module' ? 'modules' : 'themes';
        return self::$installedAddons[$key][$addonId] ?? null;
    }

    /**
     * Check if an add-on is installed via Composer.
     *
     * @param string $addonId The add-on directory name
     * @param string $type 'module' or 'theme'
     */
    public function isComposerInstalled(string $addonId, string $type = 'module'): bool
    {
        return $this->getFromComposerInstalled($addonId, $type) !== null;
    }

    /**
     * Build info array from a Composer package entry.
     */
    protected function buildInfoFromPackage(array $package): array
    {
        $extra = $package['extra'] ?? [];
        $info = [];

        // Map standard composer.json fields.
        foreach ($this->composerToIniMap as $composerKey => $iniKey) {
            if (isset($package[$composerKey]) && $package[$composerKey] !== '') {
                $info[$iniKey] = $package[$composerKey];
            }
        }

        // Map extra fields.
        foreach ($this->extraToIniMap as $extraKey => $iniKey) {
            if (isset($extra[$extraKey])) {
                $info[$iniKey] = $extra[$extraKey];
            }
        }

        // Map keywords to tags.
        if (isset($package['keywords']) && is_array($package['keywords'])) {
            $keywords = array_filter($package['keywords'], function ($keyword) {
                return !in_array(strtolower($keyword), [
                    'omeka',
                    'omeka s',
                    'omeka-s',
                    'omeka s module',
                    'omeka module',
                    'module',
                    'omeka s theme',
                    'omeka theme',
                    'theme',
                ]);
            });
            if (count($keywords)) {
                $info['tags'] = implode(', ', $keywords);
            }
        }

        // Map authors.
        if (isset($package['authors'][0])) {
            $firstAuthor = $package['authors'][0];
            if (isset($firstAuthor['name'])) {
                $info['author'] = $firstAuthor['name'];
            }
            if (isset($firstAuthor['homepage'])) {
                $info['author_link'] = $firstAuthor['homepage'];
            }
        }

        // Map support.
        if (isset($package['support']['issues'])) {
            $info['support_link'] = $package['support']['issues'];
        }

        // Specific: theme_link for themes.
        if (isset($package['homepage'])) {
            $info['theme_link'] = $package['homepage'];
        }

        // Apply defaults.
        if (empty($info['name'])) {
            $info['name'] = $this->projectNameToLabel($package['name'] ?? '');
        }

        // Version: prefer addon-version, then package version.
        if (empty($info['version'])) {
            $version = $package['version'] ?? '1.0.0';
            $info['version'] = ltrim($version, 'vV');
        }

        $info['configurable'] = !empty($info['configurable']);

        return $info;
    }

    /**
     * Clear the installed.json cache, mainly for testing.
     */
    public static function clearCache(): void
    {
        self::$installedAddons = null;
    }
}
