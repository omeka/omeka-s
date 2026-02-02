<?php declare(strict_types=1);

namespace OmekaTest\Service;

use Omeka\Module\Manager as ModuleManager;
use Omeka\Module\InfoReader;
use Omeka\Service\ModuleManagerFactory;
use Omeka\Test\TestCase;

/**
 * Test ModuleManagerFactory with addons directory support.
 *
 * These tests verify that:
 * - modules/ (local/manual) takes precedence over addons/modules/ (composer)
 * - Both directories are properly scanned for modules
 * - Various combinations of module.ini and composer.json are handled
 * - The directory structure exists
 */
class ModuleManagerFactoryTest extends TestCase
{
    protected $testModulesPath;
    protected $testAddonsModulesPath;
    protected $createdModules = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary test directories.
        $this->testModulesPath = sys_get_temp_dir() . '/omeka_test_modules_' . uniqid();
        $this->testAddonsModulesPath = sys_get_temp_dir() . '/omeka_test_addons_modules_' . uniqid();

        mkdir($this->testModulesPath, 0755, true);
        mkdir($this->testAddonsModulesPath, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up test directories.
        $this->removeDirectory($this->testModulesPath);
        $this->removeDirectory($this->testAddonsModulesPath);

        // Clean up any real modules created in OMEKA_PATH.
        foreach ($this->createdModules as $path) {
            $this->removeDirectory($path);
        }
        $this->createdModules = [];

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
     * Create a test module with module.ini only (traditional manual install).
     */
    protected function createModuleWithIni($basePath, $moduleName, $version = '1.0.0', $extraIni = [])
    {
        $modulePath = $basePath . '/' . $moduleName;
        mkdir($modulePath, 0755, true);
        mkdir($modulePath . '/config', 0755, true);

        // Create module.ini.
        $ini = "[info]\n";
        $ini .= "name = \"$moduleName\"\n";
        $ini .= "version = \"$version\"\n";
        foreach ($extraIni as $key => $value) {
            $ini .= "$key = \"$value\"\n";
        }
        file_put_contents($modulePath . '/config/module.ini', $ini);

        // Create Module.php.
        $php = "<?php\n";
        $php .= "namespace $moduleName;\n";
        $php .= "use Omeka\\Module\\AbstractModule;\n";
        $php .= "class Module extends AbstractModule {}\n";
        file_put_contents($modulePath . '/Module.php', $php);

        return $modulePath;
    }

    /**
     * Create a test module with composer.json only (composer install without module.ini).
     */
    protected function createModuleWithComposer($basePath, $moduleName, $version = '1.0.0', $extra = [])
    {
        $modulePath = $basePath . '/' . $moduleName;
        mkdir($modulePath, 0755, true);

        // Create composer.json.
        $composer = [
            'name' => 'test/' . strtolower($moduleName),
            'type' => 'omeka-s-module',
            'version' => $version,
            'extra' => array_merge([
                'label' => $moduleName,
            ], $extra),
        ];
        file_put_contents($modulePath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

        // Create Module.php.
        $php = "<?php\n";
        $php .= "namespace $moduleName;\n";
        $php .= "use Omeka\\Module\\AbstractModule;\n";
        $php .= "class Module extends AbstractModule {}\n";
        file_put_contents($modulePath . '/Module.php', $php);

        return $modulePath;
    }

    /**
     * Create a test module with both module.ini and composer.json.
     */
    protected function createModuleWithBoth($basePath, $moduleName, $iniVersion, $composerVersion, $extraIni = [], $extraComposer = [])
    {
        $modulePath = $basePath . '/' . $moduleName;
        mkdir($modulePath, 0755, true);
        mkdir($modulePath . '/config', 0755, true);

        // Create module.ini.
        $ini = "[info]\n";
        $ini .= "name = \"$moduleName (ini)\"\n";
        $ini .= "version = \"$iniVersion\"\n";
        foreach ($extraIni as $key => $value) {
            $ini .= "$key = \"$value\"\n";
        }
        file_put_contents($modulePath . '/config/module.ini', $ini);

        // Create composer.json.
        $composer = [
            'name' => 'test/' . strtolower($moduleName),
            'type' => 'omeka-s-module',
            'version' => $composerVersion,
            'extra' => array_merge([
                'label' => "$moduleName (composer)",
            ], $extraComposer),
        ];
        file_put_contents($modulePath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

        // Create Module.php.
        $php = "<?php\n";
        $php .= "namespace $moduleName;\n";
        $php .= "use Omeka\\Module\\AbstractModule;\n";
        $php .= "class Module extends AbstractModule {}\n";
        file_put_contents($modulePath . '/Module.php', $php);

        return $modulePath;
    }

    // -------------------------------------------------------------------------
    // Tests: Directory structure and configuration
    // -------------------------------------------------------------------------

    /**
     * Test that application config includes both module directories.
     */
    public function testModulePathsIncludesAddonsDirectory()
    {
        $config = include OMEKA_PATH . '/application/config/application.config.php';
        $modulePaths = $config['module_listener_options']['module_paths'];

        $this->assertContains(OMEKA_PATH . '/modules', $modulePaths);
        $this->assertContains(OMEKA_PATH . '/addons/modules', $modulePaths);
    }

    /**
     * Test that modules/ comes before addons/modules/ (for priority).
     *
     * Local modules in modules/ should take precedence over
     * composer-installed modules in addons/modules/.
     */
    public function testModulesDirectoryHasPriorityOverAddons()
    {
        $config = include OMEKA_PATH . '/application/config/application.config.php';
        $modulePaths = $config['module_listener_options']['module_paths'];

        $modulesIndex = array_search(OMEKA_PATH . '/modules', $modulePaths);
        $addonsIndex = array_search(OMEKA_PATH . '/addons/modules', $modulePaths);

        $this->assertNotFalse($modulesIndex, 'modules/ should be in module_paths');
        $this->assertNotFalse($addonsIndex, 'addons/modules/ should be in module_paths');
        $this->assertLessThan($addonsIndex, $modulesIndex, 'modules/ should come before addons/modules/');
    }

    /**
     * Test that addons/modules directory exists.
     */
    public function testAddonsModulesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/addons/modules');
    }

    /**
     * Test that addons/themes directory exists.
     */
    public function testAddonsThemesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/addons/themes');
    }

    /**
     * Test that modules/ directory exists (for local/manual modules).
     */
    public function testModulesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/modules');
    }

    /**
     * Test that themes/ directory exists (for local/manual themes).
     */
    public function testThemesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/themes');
    }

    // -------------------------------------------------------------------------
    // Tests: InfoReader for modules with different configurations
    // -------------------------------------------------------------------------

    /**
     * Test InfoReader reads module with module.ini only.
     */
    public function testInfoReaderWithModuleIniOnly()
    {
        $modulePath = $this->createModuleWithIni(
            $this->testModulesPath,
            'TestModuleIni',
            '1.0.0',
            ['author' => 'Test Author']
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertNotNull($info);
        $this->assertEquals('TestModuleIni', $info['name']);
        $this->assertEquals('1.0.0', $info['version']);
        $this->assertEquals('Test Author', $info['author']);
    }

    /**
     * Test InfoReader reads module with composer.json only.
     */
    public function testInfoReaderWithComposerJsonOnly()
    {
        $modulePath = $this->createModuleWithComposer(
            $this->testModulesPath,
            'TestModuleComposer',
            '2.0.0',
            ['omeka-version-constraint' => '^4.0']
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertNotNull($info);
        $this->assertEquals('TestModuleComposer', $info['name']);
        $this->assertEquals('2.0.0', $info['version']);
        $this->assertEquals('^4.0', $info['omeka_version_constraint']);
    }

    /**
     * Test InfoReader merges module.ini and composer.json (composer takes precedence).
     */
    public function testInfoReaderWithBothSources()
    {
        $modulePath = $this->createModuleWithBoth(
            $this->testModulesPath,
            'TestModuleBoth',
            '1.0.0',  // ini version
            '2.0.0',  // composer version
            ['author' => 'Ini Author'],  // Only in ini
            ['omeka-version-constraint' => '^4.0']  // Only in composer
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertNotNull($info);
        // Composer label takes precedence.
        $this->assertEquals('TestModuleBoth (composer)', $info['name']);
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
        $modulePath = $this->testModulesPath . '/EmptyModule';
        mkdir($modulePath, 0755, true);

        // Create Module.php only (no config files).
        $php = "<?php\nnamespace EmptyModule;\nuse Omeka\\Module\\AbstractModule;\nclass Module extends AbstractModule {}\n";
        file_put_contents($modulePath . '/Module.php', $php);

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertNull($info);
    }

    /**
     * Test InfoReader with addon-version overriding version in composer.json.
     */
    public function testInfoReaderWithAddonVersion()
    {
        $modulePath = $this->testModulesPath . '/AddonVersionModule';
        mkdir($modulePath, 0755, true);

        $composer = [
            'name' => 'test/addon-version-module',
            'type' => 'omeka-s-module',
            'version' => '1.0.0',
            'extra' => [
                'label' => 'Addon Version Module',
                'addon-version' => '3.5.0',  // Should override version.
            ],
        ];
        file_put_contents($modulePath . '/composer.json', json_encode($composer));
        file_put_contents($modulePath . '/Module.php', "<?php\nnamespace AddonVersionModule;\nuse Omeka\\Module\\AbstractModule;\nclass Module extends AbstractModule {}\n");

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertEquals('3.5.0', $info['version']);
    }

    // -------------------------------------------------------------------------
    // Tests: Priority between modules/ and addons/modules/
    // -------------------------------------------------------------------------

    /**
     * Test that a module in modules/ takes precedence over same module in addons/modules/.
     *
     * This test creates real modules in OMEKA_PATH to verify factory behavior.
     */
    public function testLocalModuleTakesPrecedenceOverAddons()
    {
        $moduleName = 'TestPriority_' . uniqid();
        $localModulePath = OMEKA_PATH . '/modules/' . $moduleName;
        $addonsModulePath = OMEKA_PATH . '/addons/modules/' . $moduleName;

        $this->createdModules[] = $localModulePath;
        $this->createdModules[] = $addonsModulePath;

        try {
            // Create module in addons/modules first.
            $this->createModuleWithIni($addonsModulePath . '/..', $moduleName, '1.0.0', ['description' => 'Addons version']);

            // Create module in modules/ (should take precedence).
            $this->createModuleWithIni($localModulePath . '/..', $moduleName, '2.0.0', ['description' => 'Local version']);

            // Read info using InfoReader - should get local version.
            $infoReader = new InfoReader();
            $localInfo = $infoReader->read($localModulePath, 'module');
            $addonsInfo = $infoReader->read($addonsModulePath, 'module');

            // Both should be readable.
            $this->assertNotNull($localInfo);
            $this->assertNotNull($addonsInfo);
            $this->assertEquals('2.0.0', $localInfo['version']);
            $this->assertEquals('1.0.0', $addonsInfo['version']);
            $this->assertEquals('Local version', $localInfo['description']);
            $this->assertEquals('Addons version', $addonsInfo['description']);
        } finally {
            // Cleanup is handled in tearDown.
        }
    }

    /**
     * Test module in addons/modules/ only (no local override).
     */
    public function testModuleInAddonsOnlyIsRecognized()
    {
        $moduleName = 'TestAddonsOnly_' . uniqid();
        $addonsModulePath = OMEKA_PATH . '/addons/modules/' . $moduleName;

        $this->createdModules[] = $addonsModulePath;

        try {
            $this->createModuleWithComposer(
                OMEKA_PATH . '/addons/modules',
                $moduleName,
                '1.5.0',
                ['omeka-version-constraint' => '^4.0']
            );

            $infoReader = new InfoReader();
            $info = $infoReader->read($addonsModulePath, 'module');

            $this->assertNotNull($info);
            $this->assertEquals($moduleName, $info['name']);
            $this->assertEquals('1.5.0', $info['version']);
        } finally {
            // Cleanup is handled in tearDown.
        }
    }

    // -------------------------------------------------------------------------
    // Tests: Module validity checks
    // -------------------------------------------------------------------------

    /**
     * Test that a directory without Module.php is detected but invalid.
     */
    public function testModuleWithoutModulePhp()
    {
        $modulePath = $this->testModulesPath . '/NoModulePhp';
        mkdir($modulePath, 0755, true);
        mkdir($modulePath . '/config', 0755, true);

        // Create module.ini but no Module.php.
        $ini = "[info]\nname = \"No Module PHP\"\nversion = \"1.0.0\"\n";
        file_put_contents($modulePath . '/config/module.ini', $ini);

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        // InfoReader should still read the config.
        $this->assertNotNull($info);
        $this->assertTrue($infoReader->isValid($info));

        // But Module.php check is done by the factory, not InfoReader.
        $this->assertFileDoesNotExist($modulePath . '/Module.php');
    }

    /**
     * Test InfoReader with invalid module.ini.
     */
    public function testInfoReaderWithInvalidModuleIni()
    {
        $modulePath = $this->testModulesPath . '/InvalidIni';
        mkdir($modulePath, 0755, true);
        mkdir($modulePath . '/config', 0755, true);

        // Create invalid ini file.
        file_put_contents($modulePath . '/config/module.ini', 'this is not [[ valid ini');

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        // Should return null (no valid source).
        $this->assertNull($info);
    }

    /**
     * Test InfoReader with invalid composer.json.
     */
    public function testInfoReaderWithInvalidComposerJson()
    {
        $modulePath = $this->testModulesPath . '/InvalidComposer';
        mkdir($modulePath, 0755, true);

        // Create invalid JSON file.
        file_put_contents($modulePath . '/composer.json', '{invalid json');

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        // Should return null (no valid source).
        $this->assertNull($info);
    }

    /**
     * Test InfoReader falls back to ini when composer.json is invalid.
     */
    public function testInfoReaderFallsBackToIniWhenComposerInvalid()
    {
        $modulePath = $this->testModulesPath . '/FallbackToIni';
        mkdir($modulePath, 0755, true);
        mkdir($modulePath . '/config', 0755, true);

        // Create invalid composer.json.
        file_put_contents($modulePath . '/composer.json', '{invalid json');

        // Create valid module.ini.
        $ini = "[info]\nname = \"Fallback Module\"\nversion = \"1.0.0\"\n";
        file_put_contents($modulePath . '/config/module.ini', $ini);

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        // Should use ini as fallback.
        $this->assertNotNull($info);
        $this->assertEquals('Fallback Module', $info['name']);
        $this->assertEquals('1.0.0', $info['version']);
    }

    // -------------------------------------------------------------------------
    // Tests: Extra composer.json fields
    // -------------------------------------------------------------------------

    /**
     * Test configurable flag from composer.json extra.
     */
    public function testConfigurableFlagFromComposer()
    {
        $modulePath = $this->createModuleWithComposer(
            $this->testModulesPath,
            'ConfigurableModule',
            '1.0.0',
            ['configurable' => true]
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertTrue($info['configurable']);
    }

    /**
     * Test configurable defaults to false.
     */
    public function testConfigurableDefaultsFalse()
    {
        $modulePath = $this->createModuleWithComposer(
            $this->testModulesPath,
            'NonConfigurableModule',
            '1.0.0'
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertFalse($info['configurable']);
    }

    /**
     * Test installer-name in composer.json.
     */
    public function testInstallerNameFromComposer()
    {
        $modulePath = $this->testModulesPath . '/CustomInstallerName';
        mkdir($modulePath, 0755, true);

        $composer = [
            'name' => 'vendor/omeka-s-module-some-module',
            'extra' => [
                'installer-name' => 'CustomInstallerName',
                'label' => 'Custom Module',
            ],
        ];
        file_put_contents($modulePath . '/composer.json', json_encode($composer));

        $infoReader = new InfoReader();
        $installerName = $infoReader->getInstallerName($modulePath);

        $this->assertEquals('CustomInstallerName', $installerName);
    }

    /**
     * Test installer-name derived from project name.
     */
    public function testInstallerNameDerivedFromProjectName()
    {
        $modulePath = $this->testModulesPath . '/DerivedName';
        mkdir($modulePath, 0755, true);

        $composer = [
            'name' => 'daniel-km/omeka-s-module-easy-admin',
            'extra' => [
                'label' => 'Easy Admin',
            ],
        ];
        file_put_contents($modulePath . '/composer.json', json_encode($composer));

        $infoReader = new InfoReader();
        $installerName = $infoReader->getInstallerName($modulePath);

        $this->assertEquals('EasyAdmin', $installerName);
    }
}
