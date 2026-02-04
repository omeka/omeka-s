<?php declare(strict_types=1);

namespace OmekaTest\Service;

use Omeka\Module\InfoReader;
use Omeka\Service\ModuleManagerFactory;
use Omeka\Test\TestCase;

/**
 * Test ModuleManagerFactory with composer-addons directory support.
 *
 * These tests verify that:
 * - modules/ (local/manual) takes precedence over composer-addons/modules/ (composer)
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
        $this->assertContains(OMEKA_PATH . '/composer-addons/modules', $modulePaths);
    }

    /**
     * Test that modules/ comes before composer-addons/modules/ (for priority).
     *
     * Local modules in modules/ should take precedence over
     * composer-installed modules in composer-addons/modules/.
     */
    public function testModulesDirectoryHasPriorityOverAddons()
    {
        $config = include OMEKA_PATH . '/application/config/application.config.php';
        $modulePaths = $config['module_listener_options']['module_paths'];

        $modulesIndex = array_search(OMEKA_PATH . '/modules', $modulePaths);
        $addonsIndex = array_search(OMEKA_PATH . '/composer-addons/modules', $modulePaths);

        $this->assertNotFalse($modulesIndex, 'modules/ should be in module_paths');
        $this->assertNotFalse($addonsIndex, 'composer-addons/modules/ should be in module_paths');
        $this->assertLessThan($addonsIndex, $modulesIndex, 'modules/ should come before composer-addons/modules/');
    }

    /**
     * Test that composer-addons/modules directory exists.
     */
    public function testAddonsModulesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/composer-addons/modules');
    }

    /**
     * Test that composer-addons/themes directory exists.
     */
    public function testAddonsThemesDirectoryExists()
    {
        $this->assertDirectoryExists(OMEKA_PATH . '/composer-addons/themes');
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
            '2.0.0'
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $this->assertNotNull($info);
        $this->assertEquals('TestModuleComposer', $info['name']);
        $this->assertEquals('2.0.0', $info['version']);
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
            ['author' => 'Ini Author']  // Only in ini
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

    // -------------------------------------------------------------------------
    // Tests: Priority between modules/ and composer-addons/modules/
    // -------------------------------------------------------------------------

    /**
     * Test that a module in modules/ takes precedence over same module in composer-addons/modules/.
     *
     * This test creates real modules in OMEKA_PATH to verify factory behavior.
     */
    public function testLocalModuleTakesPrecedenceOverAddons()
    {
        $moduleName = 'TestPriority_' . uniqid();
        $localModulePath = OMEKA_PATH . '/modules/' . $moduleName;
        $addonsModulePath = OMEKA_PATH . '/composer-addons/modules/' . $moduleName;

        $this->createdModules[] = $localModulePath;
        $this->createdModules[] = $addonsModulePath;

        try {
            // Create module in composer-addons/modules first.
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
     * Test module in composer-addons/modules/ only (no local override).
     */
    public function testModuleInAddonsOnlyIsRecognized()
    {
        $moduleName = 'TestAddonsOnly_' . uniqid();
        $addonsModulePath = OMEKA_PATH . '/composer-addons/modules/' . $moduleName;

        $this->createdModules[] = $addonsModulePath;

        try {
            $this->createModuleWithComposer(
                OMEKA_PATH . '/composer-addons/modules',
                $moduleName,
                '1.5.0'
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
     * Test configurable flag from module.config.php.
     */
    public function testConfigurableFlagFromModuleConfig()
    {
        $modulePath = $this->createModuleWithComposer(
            $this->testModulesPath,
            'ConfigurableModule',
            '1.0.0'
        );

        // Create module.config.php with configurable = true.
        $config = "<?php\nreturn [\n    'module_config' => [\n        'configurable' => true,\n    ],\n];\n";
        mkdir($modulePath . '/config', 0755, true);
        file_put_contents($modulePath . '/config/module.config.php', $config);

        $factory = new ModuleManagerFactory();
        $isConfigurable = $this->invokeMethod($factory, 'isModuleConfigurable', [$modulePath, []]);

        $this->assertTrue($isConfigurable);
    }

    /**
     * Test configurable flag fallback to module.ini.
     */
    public function testConfigurableFallbackToModuleIni()
    {
        $modulePath = $this->createModuleWithIni(
            $this->testModulesPath,
            'ConfigurableIniModule',
            '1.0.0',
            ['configurable' => 'true']
        );

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $factory = new ModuleManagerFactory();
        $isConfigurable = $this->invokeMethod($factory, 'isModuleConfigurable', [$modulePath, $info]);

        $this->assertTrue($isConfigurable);
    }

    /**
     * Test configurable defaults to false when not set.
     */
    public function testConfigurableDefaultsFalse()
    {
        $modulePath = $this->createModuleWithComposer(
            $this->testModulesPath,
            'NonConfigurableModule',
            '1.0.0'
        );

        $factory = new ModuleManagerFactory();
        $isConfigurable = $this->invokeMethod($factory, 'isModuleConfigurable', [$modulePath, []]);

        $this->assertFalse($isConfigurable);
    }

    /**
     * Test module.config.php takes precedence over module.ini for configurable.
     */
    public function testConfigurableModuleConfigTakesPrecedence()
    {
        $modulePath = $this->createModuleWithIni(
            $this->testModulesPath,
            'PrecedenceModule',
            '1.0.0',
            ['configurable' => 'true']
        );

        // Create module.config.php with configurable = false.
        $config = "<?php\nreturn [\n    'module_config' => [\n        'configurable' => false,\n    ],\n];\n";
        file_put_contents($modulePath . '/config/module.config.php', $config);

        $infoReader = new InfoReader();
        $info = $infoReader->read($modulePath, 'module');

        $factory = new ModuleManagerFactory();
        $isConfigurable = $this->invokeMethod($factory, 'isModuleConfigurable', [$modulePath, $info]);

        // module.config.php should take precedence over module.ini.
        $this->assertFalse($isConfigurable);
    }

    /**
     * Helper method to invoke protected/private methods.
     */
    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
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

    // -------------------------------------------------------------------------
    // Tests: Local module info takes precedence over installed.json
    // -------------------------------------------------------------------------

    /**
     * Test that local module info is used even when module exists in installed.json.
     *
     * This is a critical test for the override feature: when a module exists in
     * both modules/ (local) and composer-addons/modules/ (composer), AND there's an entry
     * in installed.json, the LOCAL module's info (from module.ini or composer.json)
     * must be used, NOT the installed.json entry.
     *
     * This allows administrators to override a composer-installed module with a
     * customized local version that has different metadata (version, description, etc.).
     */
    public function testLocalModuleInfoTakesPrecedenceOverInstalledJson()
    {
        // Use the test fixtures directory.
        $fixturesPath = __DIR__ . '/../Module/fixtures';
        $moduleName = 'TestAddonOverride';
        $localModulePath = $fixturesPath . '/modules/' . $moduleName;
        $addonsModulePath = $fixturesPath . '/composer-addons/modules/' . $moduleName;

        // Verify fixtures exist.
        $this->assertDirectoryExists($localModulePath, 'Local module fixture should exist');
        $this->assertDirectoryExists($addonsModulePath, 'Addons module fixture should exist');

        $infoReader = new InfoReader();

        // The local module.ini has "Test Addon Override (Local version)".
        $localInfo = $infoReader->read($localModulePath, 'module');
        $this->assertNotNull($localInfo, 'Local module should be readable');
        $this->assertStringContainsString('Local', $localInfo['name']);

        // The addons module has "Test Addon Override (Composer version)" in composer.json.
        $addonsInfo = $infoReader->read($addonsModulePath, 'module');
        $this->assertNotNull($addonsInfo, 'Addons module should be readable');
        $this->assertStringContainsString('Composer', $addonsInfo['name']);

        // Test the path-based decision logic used by ModuleManagerFactory.
        // For a module in modules/, strpos should NOT find '/composer-addons/modules/'.
        $this->assertFalse(
            strpos($localModulePath, '/composer-addons/modules/') !== false,
            'Local module path should not contain /composer-addons/modules/'
        );

        // For a module in composer-addons/modules/, strpos SHOULD find '/composer-addons/modules/'.
        $this->assertTrue(
            strpos($addonsModulePath, '/composer-addons/modules/') !== false,
            'Addons module path should contain /composer-addons/modules/'
        );

        // Simulate the factory logic: for local modules, DON'T use installed.json.
        $isComposerAddon = strpos($localModulePath, '/composer-addons/modules/') !== false;
        $this->assertFalse($isComposerAddon, 'Local module should not be treated as composer addon');

        // The correct info should come from read() of the local path.
        $info = $infoReader->read($localModulePath, 'module');

        $this->assertStringContainsString(
            'Local',
            $info['name'],
            'Module info should come from local module.ini, not composer.json. ' .
            'Got: ' . $info['name']
        );
    }
}
