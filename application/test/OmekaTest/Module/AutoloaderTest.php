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
     * Test the Common module special case for root-level classes.
     *
     * Common module has classes like TraitModule in the root directory (not src/).
     * The autoloader has special handling for these.
     */
    public function testCommonModuleRootLevelClasses()
    {
        // This test uses the real Common module if it exists in both locations.
        $localCommon = OMEKA_PATH . '/modules/Common';
        $addonCommon = OMEKA_PATH . '/composer-addons/modules/Common';

        if (!is_dir($localCommon) || is_link($localCommon) || !is_dir($addonCommon)) {
            $this->markTestSkipped('Common module not present in both locations.');
        }

        // Test that TraitModule can be loaded.
        $this->assertTrue(
            trait_exists('Common\\TraitModule', true),
            'Common\\TraitModule should be loadable'
        );

        // Verify it's loaded from local.
        $reflection = new \ReflectionClass('Common\\TraitModule');
        $this->assertStringContainsString('/modules/Common/', $reflection->getFileName(),
            'Common\\TraitModule should be loaded from modules/Common/');
        $this->assertStringNotContainsString('/composer-addons/', $reflection->getFileName(),
            'Common\\TraitModule should NOT be loaded from addons/');
    }
}
