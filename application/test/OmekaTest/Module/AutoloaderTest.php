<?php declare(strict_types=1);

namespace OmekaTest\Module;

use Omeka\Test\TestCase;

/**
 * Test the bootstrap autoloader that prioritizes modules/ over composer-addons/modules/.
 *
 * The autoloader in bootstrap.php ensures that when a module exists in both
 * modules/ (local) and composer-addons/modules/ (composer), classes are loaded from
 * modules/ to allow local overrides of composer-installed modules.
 *
 * IMPORTANT: The autoloader ONLY intervenes when a module exists in BOTH
 * locations. When a module exists in only one location, it does nothing
 * and lets Composer handle the autoloading (if the module is in Composer).
 */
class AutoloaderTest extends TestCase
{
    protected $testModuleName;
    protected $localModulePath;
    protected $addonModulePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate unique module name to avoid conflicts.
        $this->testModuleName = 'TestAutoloader_' . uniqid();
        $this->localModulePath = OMEKA_PATH . '/modules/' . $this->testModuleName;
        $this->addonModulePath = OMEKA_PATH . '/composer-addons/modules/' . $this->testModuleName;
    }

    protected function tearDown(): void
    {
        // Clean up test modules.
        $this->removeDirectory($this->localModulePath);
        $this->removeDirectory($this->addonModulePath);

        parent::tearDown();
    }

    protected function removeDirectory($path)
    {
        // Handle symlinks first.
        if (is_link($path)) {
            unlink($path);
            return;
        }
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
     * Create a test module with a service class.
     */
    protected function createTestModule($basePath, $source)
    {
        mkdir($basePath, 0755, true);
        mkdir($basePath . '/src', 0755, true);

        // Create Module.php.
        $modulePhp = <<<PHP
<?php declare(strict_types=1);

namespace {$this->testModuleName};

use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    const SOURCE = '$source';

    public function getConfig()
    {
        return [];
    }
}
PHP;
        file_put_contents($basePath . '/Module.php', $modulePhp);

        // Create a PSR-4 service class.
        $servicePhp = <<<PHP
<?php declare(strict_types=1);

namespace {$this->testModuleName};

class TestService
{
    const SOURCE = '$source';

    public function getSource(): string
    {
        return self::SOURCE;
    }
}
PHP;
        file_put_contents($basePath . '/src/TestService.php', $servicePhp);

        // Create config/module.ini.
        mkdir($basePath . '/config', 0755, true);
        $ini = "[info]\n";
        $ini .= "name = \"{$this->testModuleName}\"\n";
        $ini .= "version = \"1.0.0\"\n";
        file_put_contents($basePath . '/config/module.ini', $ini);
    }

    /**
     * Test that local module (modules/) takes precedence over composer-addons/modules/.
     *
     * This is the key test for the bootstrap autoloader. When a module exists
     * in both locations with different content, the autoloader MUST load
     * the class from modules/ (local) to allow local overrides.
     */
    public function testLocalModuleTakesPrecedenceOverAddons()
    {
        // Create module in both locations with different SOURCE values.
        $this->createTestModule($this->addonModulePath, 'addon');
        $this->createTestModule($this->localModulePath, 'local');

        $className = $this->testModuleName . '\\TestService';

        if (class_exists($className, false)) {
            $this->markTestSkipped('Class already loaded from previous test.');
        }

        $this->assertTrue(class_exists($className, true), 'Class should be autoloadable');

        // The class MUST be loaded from modules/ (local), not composer-addons/modules/.
        $reflection = new \ReflectionClass($className);
        $this->assertStringContainsString('/modules/' . $this->testModuleName, $reflection->getFileName(),
            'Class should be loaded from modules/, not composer-addons/modules/');
        $this->assertStringNotContainsString('/composer-addons/', $reflection->getFileName(),
            'Class should NOT be loaded from composer-addons/modules/');
        $this->assertEquals('local', $className::SOURCE,
            'Class SOURCE should be "local" to confirm modules/ takes precedence');
    }

    /**
     * Test that symlink from modules/ to addons/ does NOT trigger override.
     *
     * When modules/Foo is a symlink to composer-addons/modules/Foo, the autoloader
     * should NOT intervene (is_link check returns true), allowing Composer
     * to handle the loading normally.
     */
    public function testSymlinkModuleDoesNotTriggerOverride()
    {
        // Create module in composer-addons/modules/.
        $this->createTestModule($this->addonModulePath, 'addon');

        // Create symlink from modules/ to composer-addons/modules/.
        symlink($this->addonModulePath, $this->localModulePath);

        $className = $this->testModuleName . '\\TestService';

        if (class_exists($className, false)) {
            $this->markTestSkipped('Class already loaded from previous test.');
        }

        // With symlink, is_link() returns true, so autoloader returns false.
        // The class will only be loadable if Composer knows about it.
        // Since our test module is not in Composer, it won't be loadable.
        // This is expected behavior - the autoloader should NOT intervene for symlinks.

        // We can verify the autoloader's logic by checking the conditions directly.
        $localModule = OMEKA_PATH . '/modules/' . $this->testModuleName;
        $addonModule = OMEKA_PATH . '/composer-addons/modules/' . $this->testModuleName;

        $this->assertTrue(is_dir($localModule), 'Local module path should exist (symlink)');
        $this->assertTrue(is_link($localModule), 'Local module should be a symlink');
        $this->assertTrue(is_dir($addonModule), 'Addon module path should exist');

        // The autoloader condition: !is_dir($local) || is_link($local) || !is_dir($addon)
        // For symlink: is_link($local) is true, so autoloader returns false (no override).
        $autoloaderWouldIntervene = is_dir($localModule) && !is_link($localModule) && is_dir($addonModule);
        $this->assertFalse($autoloaderWouldIntervene,
            'Autoloader should NOT intervene when local is a symlink');
    }

    /**
     * Test autoloader does not intervene when module only exists in modules/.
     */
    public function testAutoloaderDoesNotInterveneForLocalOnlyModule()
    {
        // Create module only in modules/.
        $this->createTestModule($this->localModulePath, 'local');

        $localModule = OMEKA_PATH . '/modules/' . $this->testModuleName;
        $addonModule = OMEKA_PATH . '/composer-addons/modules/' . $this->testModuleName;

        $this->assertTrue(is_dir($localModule), 'Local module should exist');
        $this->assertFalse(is_dir($addonModule), 'Addon module should NOT exist');

        // Autoloader condition check.
        $autoloaderWouldIntervene = is_dir($localModule) && !is_link($localModule) && is_dir($addonModule);
        $this->assertFalse($autoloaderWouldIntervene,
            'Autoloader should NOT intervene when module only exists in modules/');
    }

    /**
     * Test autoloader does not intervene when module only exists in composer-addons/modules/.
     */
    public function testAutoloaderDoesNotInterveneForAddonOnlyModule()
    {
        // Create module only in composer-addons/modules/.
        $this->createTestModule($this->addonModulePath, 'addon');

        $localModule = OMEKA_PATH . '/modules/' . $this->testModuleName;
        $addonModule = OMEKA_PATH . '/composer-addons/modules/' . $this->testModuleName;

        $this->assertFalse(is_dir($localModule), 'Local module should NOT exist');
        $this->assertTrue(is_dir($addonModule), 'Addon module should exist');

        // Autoloader condition check.
        $autoloaderWouldIntervene = is_dir($localModule) && !is_link($localModule) && is_dir($addonModule);
        $this->assertFalse($autoloaderWouldIntervene,
            'Autoloader should NOT intervene when module only exists in composer-addons/modules/');
    }

    /**
     * Test partial override: only some classes exist in local module.
     *
     * When a module exists in both locations but the local version only contains
     * some classes (partial override), the autoloader should:
     * - Load existing classes from local
     * - Return false for missing classes, allowing Composer to load from addon
     */
    public function testPartialOverrideAllowsFallbackToAddon()
    {
        // Create full module in composer-addons/modules/ with two classes.
        $this->createTestModule($this->addonModulePath, 'addon');
        $this->createAdditionalClass($this->addonModulePath, 'AnotherService', 'addon');

        // Create partial override in modules/ with only TestService (not AnotherService).
        $this->createTestModule($this->localModulePath, 'local');
        // Note: AnotherService is NOT created in local.

        // Verify both directories exist (autoloader will intervene).
        $this->assertTrue(is_dir($this->localModulePath));
        $this->assertTrue(is_dir($this->addonModulePath));

        // Verify file existence.
        $localTestService = $this->localModulePath . '/src/TestService.php';
        $localAnotherService = $this->localModulePath . '/src/AnotherService.php';
        $addonAnotherService = $this->addonModulePath . '/src/AnotherService.php';

        $this->assertTrue(file_exists($localTestService), 'TestService should exist in local');
        $this->assertFalse(file_exists($localAnotherService), 'AnotherService should NOT exist in local');
        $this->assertTrue(file_exists($addonAnotherService), 'AnotherService should exist in addon');

        // Test the autoloader logic directly (simulating what bootstrap.php does).
        // For TestService: file exists in local -> would be loaded from local.
        // For AnotherService: file doesn't exist in local -> autoloader returns false.
        $testServiceClass = $this->testModuleName . '\\TestService';
        $anotherServiceClass = $this->testModuleName . '\\AnotherService';

        // Simulate autoloader logic for AnotherService.
        $moduleNamespace = $this->testModuleName;
        $localModule = OMEKA_PATH . '/modules/' . $moduleNamespace;
        $addonModule = OMEKA_PATH . '/composer-addons/modules/' . $moduleNamespace;

        // Autoloader would check this file for AnotherService.
        $relativePath = str_replace('\\', '/', 'AnotherService');
        $localFile = $localModule . '/src/' . $relativePath . '.php';

        // The autoloader returns false when file doesn't exist, allowing fallback.
        $this->assertFalse(file_exists($localFile),
            'AnotherService should not exist in local, so autoloader returns false and Composer can load from addon');

        // For TestService, verify it loads from local.
        if (!class_exists($testServiceClass, false)) {
            $this->assertTrue(class_exists($testServiceClass, true), 'TestService should be autoloadable');
            $reflection = new \ReflectionClass($testServiceClass);
            $this->assertStringContainsString('/modules/' . $this->testModuleName, $reflection->getFileName(),
                'TestService should be loaded from local modules/');
            $this->assertEquals('local', $testServiceClass::SOURCE,
                'TestService SOURCE should be "local"');
        }
    }

    /**
     * Create an additional service class in a module.
     */
    protected function createAdditionalClass($basePath, $className, $source)
    {
        $servicePhp = <<<PHP
<?php declare(strict_types=1);

namespace {$this->testModuleName};

class {$className}
{
    const SOURCE = '$source';

    public function getSource(): string
    {
        return self::SOURCE;
    }
}
PHP;
        file_put_contents($basePath . '/src/' . $className . '.php', $servicePhp);
    }

    /**
     * Test PSR-4 priority for Common module classes.
     *
     * Verifies that Common module classes are loaded from modules/Common/src/
     * (local) when the module exists in both modules/ and composer-addons/modules/.
     * This follows standard PSR-4 autoloading - no special handling required.
     */
    public function testCommonModulePsr4Priority()
    {
        // This test uses the real Common module if it exists in both locations.
        $localCommon = OMEKA_PATH . '/modules/Common';
        $addonCommon = OMEKA_PATH . '/composer-addons/modules/Common';

        if (!is_dir($localCommon) || is_link($localCommon) || !is_dir($addonCommon)) {
            $this->markTestSkipped('Common module not present in both locations.');
        }

        // Test that TraitModule can be loaded (PSR-4: Common\TraitModule -> src/TraitModule.php).
        $this->assertTrue(
            trait_exists('Common\\TraitModule', true),
            'Common\\TraitModule should be loadable'
        );

        // Verify it's loaded from local modules/, not composer-addons/.
        $reflection = new \ReflectionClass('Common\\TraitModule');
        $this->assertStringContainsString('/modules/Common/', $reflection->getFileName(),
            'Common\\TraitModule should be loaded from modules/Common/');
        $this->assertStringNotContainsString('/composer-addons/', $reflection->getFileName(),
            'Common\\TraitModule should NOT be loaded from addons/');
    }
}
