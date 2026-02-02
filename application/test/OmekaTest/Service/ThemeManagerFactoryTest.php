<?php declare(strict_types=1);

namespace OmekaTest\Service;

use Omeka\Module\InfoReader;
use Omeka\Site\Theme\Manager as ThemeManager;
use Omeka\Service\ThemeManagerFactory;
use Omeka\Test\TestCase;

/**
 * Test ThemeManagerFactory with addons directory support.
 *
 * These tests verify that:
 * - themes/ (local/manual) takes precedence over addons/themes/ (composer)
 * - Both directories are properly scanned for themes
 * - Various combinations of theme.ini and composer.json are handled
 * - The directory structure exists
 */
class ThemeManagerFactoryTest extends TestCase
{
    protected $testThemesPath;
    protected $testAddonsThemesPath;
    protected $createdThemes = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary test directories.
        $this->testThemesPath = sys_get_temp_dir() . '/omeka_test_themes_' . uniqid();
        $this->testAddonsThemesPath = sys_get_temp_dir() . '/omeka_test_addons_themes_' . uniqid();

        mkdir($this->testThemesPath, 0755, true);
        mkdir($this->testAddonsThemesPath, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up test directories.
        $this->removeDirectory($this->testThemesPath);
        $this->removeDirectory($this->testAddonsThemesPath);

        // Clean up any real themes created in OMEKA_PATH.
        foreach ($this->createdThemes as $path) {
            $this->removeDirectory($path);
        }
        $this->createdThemes = [];

        parent::tearDown();
    }

    protected function removeDirectory($path)
    {
        if (!is_dir($path)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($path);
    }

    /**
     * Create a test theme with theme.ini only (traditional manual install).
     */
    protected function createThemeWithIni($basePath, $themeName, $version = '1.0.0', $extraIni = [])
    {
        $themePath = $basePath . '/' . $themeName;
        mkdir($themePath, 0755, true);
        mkdir($themePath . '/config', 0755, true);

        // Create theme.ini.
        $ini = "[info]\n";
        $ini .= "name = \"$themeName\"\n";
        $ini .= "version = \"$version\"\n";
        foreach ($extraIni as $key => $value) {
            $ini .= "$key = \"$value\"\n";
        }
        file_put_contents($themePath . '/config/theme.ini', $ini);

        return $themePath;
    }

    /**
     * Create a test theme with composer.json only (composer install without theme.ini).
     */
    protected function createThemeWithComposer($basePath, $themeName, $version = '1.0.0', $extra = [])
    {
        $themePath = $basePath . '/' . $themeName;
        mkdir($themePath, 0755, true);

        // Create composer.json.
        $composer = [
            'name' => 'test/' . strtolower($themeName),
            'type' => 'omeka-s-theme',
            'version' => $version,
            'homepage' => 'https://example.com/theme/' . strtolower($themeName),
            'extra' => array_merge([
                'label' => $themeName,
            ], $extra),
        ];
        file_put_contents($themePath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

        return $themePath;
    }

    /**
     * Create a test theme with both theme.ini and composer.json.
     */
    protected function createThemeWithBoth($basePath, $themeName, $iniVersion, $composerVersion, $extraIni = [], $extraComposer = [])
    {
        $themePath = $basePath . '/' . $themeName;
        mkdir($themePath, 0755, true);
        mkdir($themePath . '/config', 0755, true);

        // Create theme.ini.
        $ini = "[info]\n";
        $ini .= "name = \"$themeName (ini)\"\n";
        $ini .= "version = \"$iniVersion\"\n";
        foreach ($extraIni as $key => $value) {
            $ini .= "$key = \"$value\"\n";
        }
        file_put_contents($themePath . '/config/theme.ini', $ini);

        // Create composer.json.
        $composer = [
            'name' => 'test/' . strtolower($themeName),
            'type' => 'omeka-s-theme',
            'version' => $composerVersion,
            'homepage' => 'https://example.com/theme/' . strtolower($themeName),
            'extra' => array_merge([
                'label' => "$themeName (composer)",
            ], $extraComposer),
        ];
        file_put_contents($themePath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

        return $themePath;
    }

    // Alias for backward compatibility with existing tests.
    protected function createTestTheme($basePath, $themeName, $version = '1.0.0')
    {
        return $this->createThemeWithIni($basePath, $themeName, $version);
    }

    /**
     * Test that addons/themes directory exists.
     */
    public function testAddonsThemesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/addons/themes');
    }

    /**
     * Test that themes/ directory exists (for local/manual themes).
     */
    public function testThemesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/themes');
    }

    /**
     * Test that a theme from addons/themes has correct basePath.
     */
    public function testThemeFromAddonsDirectoryHasCorrectBasePath()
    {
        // Create a theme in the real addons directory for testing.
        $addonsThemePath = OMEKA_PATH . '/addons/themes/TestThemeAddons_' . uniqid();
        $themeName = basename($addonsThemePath);

        try {
            mkdir($addonsThemePath, 0755, true);
            mkdir($addonsThemePath . '/config', 0755, true);

            $ini = "[info]\n";
            $ini .= "name = \"Test Theme Addons\"\n";
            $ini .= "version = \"1.0.0\"\n";
            file_put_contents($addonsThemePath . '/config/theme.ini', $ini);

            // Create the factory and invoke it.
            $config = [
                'page_templates' => [],
                'block_templates' => [],
            ];
            $serviceManager = $this->getServiceManager([
                'Config' => $config,
            ]);

            $factory = new ThemeManagerFactory();
            $manager = $factory($serviceManager, 'Omeka\Site\ThemeManager');

            // Verify theme was registered with correct basePath.
            $theme = $manager->getTheme($themeName);
            $this->assertNotNull($theme);
            $this->assertEquals('addons/themes', $theme->getBasePath());
            $this->assertStringContainsString('/addons/themes/' . $themeName, $theme->getPath());
            $this->assertEquals('/addons/themes/' . $themeName . '/theme.jpg', $theme->getThumbnail());
        } finally {
            // Clean up.
            $this->removeDirectory($addonsThemePath);
        }
    }

    /**
     * Test that a theme from themes/ (standard) has correct basePath.
     */
    public function testThemeFromStandardDirectoryHasCorrectBasePath()
    {
        // Find an existing theme in the standard directory.
        $themesDir = OMEKA_PATH . '/themes';
        $foundTheme = null;

        foreach (new \DirectoryIterator($themesDir) as $dir) {
            if ($dir->isDir() && !$dir->isDot()) {
                $iniFile = $dir->getPathname() . '/config/theme.ini';
                if (file_exists($iniFile)) {
                    $foundTheme = $dir->getBasename();
                    break;
                }
            }
        }

        if (!$foundTheme) {
            $this->markTestSkipped('No standard theme found for testing.');
        }

        $config = [
            'page_templates' => [],
            'block_templates' => [],
        ];
        $serviceManager = $this->getServiceManager([
            'Config' => $config,
        ]);

        $factory = new ThemeManagerFactory();
        $manager = $factory($serviceManager, 'Omeka\Site\ThemeManager');

        $theme = $manager->getTheme($foundTheme);
        $this->assertNotNull($theme);
        $this->assertEquals('themes', $theme->getBasePath());
        $this->assertStringContainsString('/themes/' . $foundTheme, $theme->getPath());
        $this->assertStringNotContainsString('/addons/themes/', $theme->getPath());
    }

    /**
     * Test that local theme (themes/) takes precedence over addons/themes/.
     *
     * When the same theme exists in both directories, the one in themes/
     * should be loaded (local override of composer-installed theme).
     */
    public function testLocalThemeTakesPrecedenceOverAddons()
    {
        // Create a theme in addons/themes.
        $themeName = 'TestPrecedence_' . uniqid();
        $addonsThemePath = OMEKA_PATH . '/addons/themes/' . $themeName;
        $localThemePath = OMEKA_PATH . '/themes/' . $themeName;

        try {
            // Create addons version first.
            mkdir($addonsThemePath, 0755, true);
            mkdir($addonsThemePath . '/config', 0755, true);
            $ini = "[info]\n";
            $ini .= "name = \"$themeName (Addons Version)\"\n";
            $ini .= "version = \"1.0.0\"\n";
            file_put_contents($addonsThemePath . '/config/theme.ini', $ini);

            // Create local version (should take precedence).
            mkdir($localThemePath, 0755, true);
            mkdir($localThemePath . '/config', 0755, true);
            $ini = "[info]\n";
            $ini .= "name = \"$themeName (Local Override)\"\n";
            $ini .= "version = \"2.0.0\"\n";
            file_put_contents($localThemePath . '/config/theme.ini', $ini);

            $config = [
                'page_templates' => [],
                'block_templates' => [],
            ];
            $serviceManager = $this->getServiceManager([
                'Config' => $config,
            ]);

            $factory = new ThemeManagerFactory();
            $manager = $factory($serviceManager, 'Omeka\Site\ThemeManager');

            // Verify the local version takes precedence.
            $theme = $manager->getTheme($themeName);
            $this->assertNotNull($theme);
            $this->assertEquals('themes', $theme->getBasePath());
            $this->assertStringContainsString('Local Override', $theme->getName());
            $this->assertStringContainsString('/themes/' . $themeName, $theme->getPath());
            $this->assertStringNotContainsString('/addons/', $theme->getPath());
        } finally {
            // Clean up.
            $this->removeDirectory($addonsThemePath);
            $this->removeDirectory($localThemePath);
        }
    }

    // -------------------------------------------------------------------------
    // Tests: InfoReader for themes with different configurations
    // -------------------------------------------------------------------------

    /**
     * Test InfoReader reads theme with theme.ini only.
     */
    public function testInfoReaderWithThemeIniOnly()
    {
        $themePath = $this->createThemeWithIni(
            $this->testThemesPath,
            'TestThemeIni',
            '1.0.0',
            ['author' => 'Test Author']
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        $this->assertNotNull($info);
        $this->assertEquals('TestThemeIni', $info['name']);
        $this->assertEquals('1.0.0', $info['version']);
        $this->assertEquals('Test Author', $info['author']);
    }

    /**
     * Test InfoReader reads theme with composer.json only.
     */
    public function testInfoReaderWithComposerJsonOnly()
    {
        $themePath = $this->createThemeWithComposer(
            $this->testThemesPath,
            'TestThemeComposer',
            '2.0.0',
            ['omeka-version-constraint' => '^4.0']
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        $this->assertNotNull($info);
        $this->assertEquals('TestThemeComposer', $info['name']);
        $this->assertEquals('2.0.0', $info['version']);
        $this->assertEquals('^4.0', $info['omeka_version_constraint']);
        // Theme should have theme_link from homepage.
        $this->assertEquals('https://example.com/theme/testthemecomposer', $info['theme_link']);
    }

    /**
     * Test InfoReader merges theme.ini and composer.json (composer takes precedence).
     */
    public function testInfoReaderWithBothSources()
    {
        $themePath = $this->createThemeWithBoth(
            $this->testThemesPath,
            'TestThemeBoth',
            '1.0.0',  // ini version
            '2.0.0',  // composer version
            ['author' => 'Ini Author'],  // Only in ini
            ['omeka-version-constraint' => '^4.0']  // Only in composer
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        $this->assertNotNull($info);
        // Composer label takes precedence.
        $this->assertEquals('TestThemeBoth (composer)', $info['name']);
        // Composer version takes precedence.
        $this->assertEquals('2.0.0', $info['version']);
        // Ini author is preserved (not in composer).
        $this->assertEquals('Ini Author', $info['author']);
        // Composer constraint is present.
        $this->assertEquals('^4.0', $info['omeka_version_constraint']);
    }

    /**
     * Test InfoReader returns null when no sources exist.
     */
    public function testInfoReaderWithNoSources()
    {
        $themePath = $this->testThemesPath . '/EmptyTheme';
        mkdir($themePath, 0755, true);

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        $this->assertNull($info);
    }

    /**
     * Test InfoReader with addon-version overriding version in composer.json.
     */
    public function testInfoReaderWithAddonVersion()
    {
        $themePath = $this->testThemesPath . '/AddonVersionTheme';
        mkdir($themePath, 0755, true);

        $composer = [
            'name' => 'test/addon-version-theme',
            'type' => 'omeka-s-theme',
            'version' => '1.0.0',
            'extra' => [
                'label' => 'Addon Version Theme',
                'addon-version' => '3.5.0',  // Should override version.
            ],
        ];
        file_put_contents($themePath . '/composer.json', json_encode($composer));

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        $this->assertEquals('3.5.0', $info['version']);
    }

    // -------------------------------------------------------------------------
    // Tests: Theme validity checks
    // -------------------------------------------------------------------------

    /**
     * Test InfoReader with invalid theme.ini.
     */
    public function testInfoReaderWithInvalidThemeIni()
    {
        $themePath = $this->testThemesPath . '/InvalidIni';
        mkdir($themePath, 0755, true);
        mkdir($themePath . '/config', 0755, true);

        // Create invalid ini file.
        file_put_contents($themePath . '/config/theme.ini', 'this is not [[ valid ini');

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        // Should return null (no valid source).
        $this->assertNull($info);
    }

    /**
     * Test InfoReader with invalid composer.json.
     */
    public function testInfoReaderWithInvalidComposerJson()
    {
        $themePath = $this->testThemesPath . '/InvalidComposer';
        mkdir($themePath, 0755, true);

        // Create invalid JSON file.
        file_put_contents($themePath . '/composer.json', '{invalid json');

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        // Should return null (no valid source).
        $this->assertNull($info);
    }

    /**
     * Test InfoReader falls back to ini when composer.json is invalid.
     */
    public function testInfoReaderFallsBackToIniWhenComposerInvalid()
    {
        $themePath = $this->testThemesPath . '/FallbackToIni';
        mkdir($themePath, 0755, true);
        mkdir($themePath . '/config', 0755, true);

        // Create invalid composer.json.
        file_put_contents($themePath . '/composer.json', '{invalid json');

        // Create valid theme.ini.
        $ini = "[info]\nname = \"Fallback Theme\"\nversion = \"1.0.0\"\n";
        file_put_contents($themePath . '/config/theme.ini', $ini);

        $infoReader = new InfoReader();
        $info = $infoReader->read($themePath, 'theme');

        // Should use ini as fallback.
        $this->assertNotNull($info);
        $this->assertEquals('Fallback Theme', $info['name']);
        $this->assertEquals('1.0.0', $info['version']);
    }

    // -------------------------------------------------------------------------
    // Tests: Theme with composer.json in addons/themes/
    // -------------------------------------------------------------------------

    /**
     * Test theme in addons/themes/ with composer.json only (no theme.ini).
     */
    public function testThemeInAddonsWithComposerJsonOnly()
    {
        $themeName = 'TestAddonsComposer_' . uniqid();
        $addonsThemePath = OMEKA_PATH . '/addons/themes/' . $themeName;

        $this->createdThemes[] = $addonsThemePath;

        try {
            mkdir($addonsThemePath, 0755, true);

            $composer = [
                'name' => 'test/' . strtolower($themeName),
                'type' => 'omeka-s-theme',
                'version' => '1.5.0',
                'description' => 'A composer-only theme',
                'homepage' => 'https://example.com/theme',
                'extra' => [
                    'label' => 'Composer Theme',
                    'omeka-version-constraint' => '^4.0',
                ],
            ];
            file_put_contents($addonsThemePath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

            $config = [
                'page_templates' => [],
                'block_templates' => [],
            ];
            $serviceManager = $this->getServiceManager([
                'Config' => $config,
            ]);

            $factory = new ThemeManagerFactory();
            $manager = $factory($serviceManager, 'Omeka\Site\ThemeManager');

            $theme = $manager->getTheme($themeName);
            $this->assertNotNull($theme, 'Theme with composer.json only should be recognized');
            $this->assertEquals('addons/themes', $theme->getBasePath());
            $this->assertEquals('Composer Theme', $theme->getName());
            $this->assertEquals('1.5.0', $theme->getIni('version'));
            $this->assertEquals('A composer-only theme', $theme->getIni('description'));
        } finally {
            // Cleanup handled by tearDown.
        }
    }

    /**
     * Test theme in addons/themes/ with both theme.ini and composer.json.
     */
    public function testThemeInAddonsWithBothSources()
    {
        $themeName = 'TestAddonsBoth_' . uniqid();
        $addonsThemePath = OMEKA_PATH . '/addons/themes/' . $themeName;

        $this->createdThemes[] = $addonsThemePath;

        try {
            mkdir($addonsThemePath, 0755, true);
            mkdir($addonsThemePath . '/config', 0755, true);

            // Create theme.ini.
            $ini = "[info]\n";
            $ini .= "name = \"$themeName from ini\"\n";
            $ini .= "version = \"1.0.0\"\n";
            $ini .= "author = \"INI Author\"\n";
            file_put_contents($addonsThemePath . '/config/theme.ini', $ini);

            // Create composer.json with different values.
            $composer = [
                'name' => 'test/' . strtolower($themeName),
                'type' => 'omeka-s-theme',
                'version' => '2.0.0',
                'description' => 'Description from composer',
                'extra' => [
                    'label' => "$themeName from composer",
                ],
            ];
            file_put_contents($addonsThemePath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

            $config = [
                'page_templates' => [],
                'block_templates' => [],
            ];
            $serviceManager = $this->getServiceManager([
                'Config' => $config,
            ]);

            $factory = new ThemeManagerFactory();
            $manager = $factory($serviceManager, 'Omeka\Site\ThemeManager');

            $theme = $manager->getTheme($themeName);
            $this->assertNotNull($theme);
            // Composer values should take precedence.
            $this->assertEquals("$themeName from composer", $theme->getName());
            $this->assertEquals('2.0.0', $theme->getIni('version'));
            $this->assertEquals('Description from composer', $theme->getIni('description'));
            // But ini values not in composer should be preserved.
            $this->assertEquals('INI Author', $theme->getIni('author'));
        } finally {
            // Cleanup handled by tearDown.
        }
    }

    // -------------------------------------------------------------------------
    // Tests: Priority between themes/ and addons/themes/
    // -------------------------------------------------------------------------

    /**
     * Test that a theme in themes/ takes precedence over same theme in addons/themes/.
     *
     * This test creates real themes in OMEKA_PATH to verify factory behavior.
     */
    public function testLocalThemeTakesPrecedenceWithBothUsingComposer()
    {
        $themeName = 'TestPriorityComposer_' . uniqid();
        $localThemePath = OMEKA_PATH . '/themes/' . $themeName;
        $addonsThemePath = OMEKA_PATH . '/addons/themes/' . $themeName;

        $this->createdThemes[] = $localThemePath;
        $this->createdThemes[] = $addonsThemePath;

        try {
            // Create theme in addons/themes with composer.json.
            mkdir($addonsThemePath, 0755, true);
            $addonsComposer = [
                'name' => 'test/' . strtolower($themeName),
                'version' => '1.0.0',
                'extra' => [
                    'label' => 'Addons Version',
                ],
            ];
            file_put_contents($addonsThemePath . '/composer.json', json_encode($addonsComposer));

            // Create theme in themes/ with composer.json (should take precedence).
            mkdir($localThemePath, 0755, true);
            $localComposer = [
                'name' => 'test/' . strtolower($themeName),
                'version' => '2.0.0',
                'extra' => [
                    'label' => 'Local Version',
                ],
            ];
            file_put_contents($localThemePath . '/composer.json', json_encode($localComposer));

            $config = [
                'page_templates' => [],
                'block_templates' => [],
            ];
            $serviceManager = $this->getServiceManager([
                'Config' => $config,
            ]);

            $factory = new ThemeManagerFactory();
            $manager = $factory($serviceManager, 'Omeka\Site\ThemeManager');

            $theme = $manager->getTheme($themeName);
            $this->assertNotNull($theme);
            // Local version should take precedence.
            $this->assertEquals('themes', $theme->getBasePath());
            $this->assertEquals('Local Version', $theme->getName());
            $this->assertEquals('2.0.0', $theme->getIni('version'));
        } finally {
            // Cleanup handled by tearDown.
        }
    }

    /**
     * Test theme in addons/themes/ only (no local override).
     */
    public function testThemeInAddonsOnlyIsRecognized()
    {
        $themeName = 'TestAddonsOnly_' . uniqid();
        $addonsThemePath = OMEKA_PATH . '/addons/themes/' . $themeName;

        $this->createdThemes[] = $addonsThemePath;

        try {
            $this->createThemeWithComposer(
                OMEKA_PATH . '/addons/themes',
                $themeName,
                '1.5.0',
                ['omeka-version-constraint' => '^4.0']
            );

            $config = [
                'page_templates' => [],
                'block_templates' => [],
            ];
            $serviceManager = $this->getServiceManager([
                'Config' => $config,
            ]);

            $factory = new ThemeManagerFactory();
            $manager = $factory($serviceManager, 'Omeka\Site\ThemeManager');

            $theme = $manager->getTheme($themeName);
            $this->assertNotNull($theme);
            $this->assertEquals('addons/themes', $theme->getBasePath());
            $this->assertEquals($themeName, $theme->getName());
            $this->assertEquals('1.5.0', $theme->getIni('version'));
        } finally {
            // Cleanup handled by tearDown.
        }
    }

    // -------------------------------------------------------------------------
    // Tests: installer-name for themes
    // -------------------------------------------------------------------------

    /**
     * Test installer-name in composer.json for themes.
     */
    public function testInstallerNameFromComposerForTheme()
    {
        $themePath = $this->testThemesPath . '/CustomInstallerName';
        mkdir($themePath, 0755, true);

        $composer = [
            'name' => 'vendor/omeka-s-theme-some-theme',
            'extra' => [
                'installer-name' => 'CustomInstallerName',
                'label' => 'Custom Theme',
            ],
        ];
        file_put_contents($themePath . '/composer.json', json_encode($composer));

        $infoReader = new InfoReader();
        $installerName = $infoReader->getInstallerName($themePath);

        $this->assertEquals('CustomInstallerName', $installerName);
    }

    /**
     * Test installer-name derived from project name for themes.
     */
    public function testInstallerNameDerivedFromProjectNameForTheme()
    {
        $themePath = $this->testThemesPath . '/DerivedName';
        mkdir($themePath, 0755, true);

        $composer = [
            'name' => 'omeka-s-themes/omeka-s-theme-foundation-s',
            'extra' => [
                'label' => 'Foundation S',
            ],
        ];
        file_put_contents($themePath . '/composer.json', json_encode($composer));

        $infoReader = new InfoReader();
        $installerName = $infoReader->getInstallerName($themePath);

        $this->assertEquals('FoundationS', $installerName);
    }
}
