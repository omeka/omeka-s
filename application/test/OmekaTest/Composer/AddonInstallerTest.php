<?php declare(strict_types=1);

namespace OmekaTest\Composer;

use Omeka\Test\TestCase;

/**
 * Test AddonInstaller for composer addon installation.
 *
 * Tests:
 * - Module and theme name inflection
 * - installer-name / install-name handling
 * - standalone flag detection
 *
 * Note: All tests are skipped if the composer/composer package is not
 * available (which is normal since it's only needed during composer
 * install/update, not at runtime).
 */
class AddonInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests if Composer classes are not available.
        // AddonInstaller extends Composer\Installer\LibraryInstaller,
        // so the class cannot even be loaded without composer/composer.
        if (!class_exists('Composer\Installer\LibraryInstaller')) {
            $this->markTestSkipped(
                'Composer classes not available. ' .
                'Run "composer require --dev composer/composer" to enable these tests.'
            );
        }
    }

    /**
     * Call protected static method via reflection.
     */
    protected function callProtectedMethod(string $method, array $args)
    {
        $reflection = new \ReflectionClass('Omeka\Composer\AddonInstaller');
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    /**
     * Create a Composer Package for testing.
     */
    protected function createComposerPackage(string $name, string $type, array $extra = [])
    {
        $class = 'Composer\Package\Package';
        $package = new $class($name, '1.0.0', '1.0.0');
        $package->setType($type);
        $package->setExtra($extra);
        return $package;
    }

    /**
     * Get AddonInstaller class (for calling static methods).
     */
    protected function getAddonInstallerClass(): string
    {
        return 'Omeka\Composer\AddonInstaller';
    }

    // -------------------------------------------------------------------------
    // Tests: Module name inflection (protected method)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider moduleNameInflectionProvider
     */
    public function testInflectModuleName($inputName, $expectedInstallName)
    {
        $result = $this->callProtectedMethod('inflectModuleName', [$inputName]);
        $this->assertEquals($expectedInstallName, $result);
    }

    public function moduleNameInflectionProvider()
    {
        return [
            // Standard prefixes (after vendor/project extraction)
            ['omeka-s-module-common', 'Common'],
            ['omeka-s-module-advanced-search', 'AdvancedSearch'],
            ['omeka-module-value-suggest', 'ValueSuggest'],

            // Without prefix
            ['value-suggest', 'ValueSuggest'],
            ['bulk-import', 'BulkImport'],

            // With suffix
            ['bulk-import-module', 'BulkImport'],
            ['neatline-omeka-s', 'Neatline'],
            ['module-lessonplans', 'Lessonplans'],

            // Mixed
            ['omeka-s-module-easy-admin-module', 'EasyAdmin'],

            // Simple names
            ['common', 'Common'],
            ['mapping', 'Mapping'],
            ['csv-import', 'CsvImport'],

            // Edge cases
            ['module', 'Module'],
            ['omeka-s', ''],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests: Theme name inflection (protected method)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider themeNameInflectionProvider
     */
    public function testInflectThemeName($inputName, $expectedInstallName)
    {
        $result = $this->callProtectedMethod('inflectThemeName', [$inputName]);
        $this->assertEquals($expectedInstallName, $result);
    }

    public function themeNameInflectionProvider()
    {
        return [
            // Standard prefixes (after vendor/project extraction)
            ['omeka-s-theme-repository', 'repository'],
            ['omeka-s-theme-foundation-s', 'foundation-s'],
            ['omeka-theme-flavor', 'flavor'],

            // Without prefix
            ['my-custom', 'my-custom'],
            ['cozy', 'cozy'],

            // With suffix
            ['my-custom-theme', 'my-custom'],
            ['flavor-theme-omeka', 'flavor'],

            // Mixed
            ['omeka-s-theme-centerrow-theme-omeka-s', 'centerrow'],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests: isStandalone static method
    // -------------------------------------------------------------------------

    public function testIsStandaloneReturnsTrueWhenSet()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage('vendor/my-module', 'omeka-s-module', ['standalone' => true]);
        $this->assertTrue($class::isStandalone($package));
    }

    public function testIsStandaloneReturnsFalseWhenNotSet()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage('vendor/my-module', 'omeka-s-module');
        $this->assertFalse($class::isStandalone($package));
    }

    public function testIsStandaloneReturnsFalseWhenExplicitlyFalse()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage('vendor/my-module', 'omeka-s-module', ['standalone' => false]);
        $this->assertFalse($class::isStandalone($package));
    }

    public function testIsStandaloneReturnsTrueForTruthyValue()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage('vendor/my-module', 'omeka-s-module', ['standalone' => 1]);
        $this->assertTrue($class::isStandalone($package));
    }

    public function testIsStandaloneReturnsFalseForFalsyValue()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage('vendor/my-module', 'omeka-s-module', ['standalone' => 0]);
        $this->assertFalse($class::isStandalone($package));
    }

    public function testIsStandaloneWorksForThemes()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage('vendor/my-theme', 'omeka-s-theme', ['standalone' => true]);
        $this->assertTrue($class::isStandalone($package));
    }

    // -------------------------------------------------------------------------
    // Tests: getInstallName static method
    // -------------------------------------------------------------------------

    public function testInstallerNameOverridesInflection()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage(
            'vendor/some-weird-name',
            'omeka-s-module',
            ['installer-name' => 'CustomModuleName']
        );
        $this->assertEquals('CustomModuleName', $class::getInstallName($package));
    }

    public function testInstallNameLegacyOverridesInflection()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage(
            'vendor/some-weird-name',
            'omeka-s-module',
            ['install-name' => 'LegacyName']
        );
        $this->assertEquals('LegacyName', $class::getInstallName($package));
    }

    public function testInstallerNameTakesPrecedenceOverInstallName()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage(
            'vendor/some-weird-name',
            'omeka-s-module',
            [
                'installer-name' => 'PreferredName',
                'install-name' => 'LegacyName',
            ]
        );
        $this->assertEquals('PreferredName', $class::getInstallName($package));
    }

    public function testGetInstallNameThrowsForPackageWithoutSlash()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must contain a slash');

        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage('invalid-package-name', 'omeka-s-module');
        $class::getInstallName($package);
    }

    // -------------------------------------------------------------------------
    // Tests: Combined scenarios
    // -------------------------------------------------------------------------

    public function testModuleWithStandaloneAndCustomName()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage(
            'vendor/omeka-s-module-test',
            'omeka-s-module',
            [
                'installer-name' => 'MyCustomModule',
                'standalone' => true,
                'label' => 'My Custom Module',
            ]
        );

        $this->assertEquals('MyCustomModule', $class::getInstallName($package));
        $this->assertTrue($class::isStandalone($package));
    }

    public function testThemeWithStandaloneAndCustomName()
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage(
            'vendor/omeka-s-theme-test',
            'omeka-s-theme',
            [
                'installer-name' => 'my-custom-theme',
                'standalone' => true,
            ]
        );

        $this->assertEquals('my-custom-theme', $class::getInstallName($package));
        $this->assertTrue($class::isStandalone($package));
    }

    // -------------------------------------------------------------------------
    // Tests: Real-world package names
    // -------------------------------------------------------------------------

    /**
     * @dataProvider realWorldModuleNamesProvider
     */
    public function testRealWorldModuleNames($packageName, $expectedInstallName)
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage($packageName, 'omeka-s-module');
        $this->assertEquals($expectedInstallName, $class::getInstallName($package));
    }

    public function realWorldModuleNamesProvider()
    {
        return [
            // Omeka official modules.
            ['omeka-s-modules/Mapping', 'Mapping'],
            ['omeka-s-modules/Collecting', 'Collecting'],
            ['omeka-s-modules/CustomVocab', 'CustomVocab'],
            ['omeka-s-modules/FacetedBrowse', 'FacetedBrowse'],

            // Various naming conventions from different developers.
            ['daniel-km/omeka-s-module-common', 'Common'],
            ['daniel-km/omeka-s-module-easy-admin', 'EasyAdmin'],
            ['zerocrates/HideProperties', 'HideProperties'],
            ['chnm/Datascribe-module', 'Datascribe'],
            ['manondamoon/omeka-s-module-group', 'Group'],
        ];
    }

    /**
     * @dataProvider realWorldThemeNamesProvider
     */
    public function testRealWorldThemeNames($packageName, $expectedInstallName)
    {
        $class = $this->getAddonInstallerClass();
        $package = $this->createComposerPackage($packageName, 'omeka-s-theme');
        $this->assertEquals($expectedInstallName, $class::getInstallName($package));
    }

    public function realWorldThemeNamesProvider()
    {
        return [
            // Omeka official themes.
            ['omeka-s-themes/default', 'default'],
            ['omeka-s-themes/cozy', 'cozy'],
            ['omeka-s-themes/centerrow', 'centerrow'],
            ['omeka-s-themes/thedaily', 'thedaily'],

            // Community themes.
            ['daniel-km/omeka-s-theme-repository', 'repository'],
        ];
    }
}
