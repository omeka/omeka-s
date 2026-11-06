<?php declare(strict_types=1);

namespace OmekaTest\Composer;

use Omeka\Composer\AddonInstaller;
use Omeka\Test\TestCase;

/**
 * Test AddonInstaller name transformations.
 *
 * These tests require composer/composer as a dev dependency.
 * They are skipped if Composer classes are not available.
 */
class AddonInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Composer\Package\PackageInterface::class)) {
            $this->markTestSkipped('Requires composer/composer dev dependency.');
        }
        parent::setUp();
    }

    /**
     * @dataProvider moduleNameProvider
     */
    public function testInflectModuleName(string $packageName, string $expected): void
    {
        $package = $this->createMockPackage($packageName, 'omeka-s-module');
        $result = AddonInstaller::getInstallName($package);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider themeNameProvider
     */
    public function testInflectThemeName(string $packageName, string $expected): void
    {
        $package = $this->createMockPackage($packageName, 'omeka-s-theme');
        $result = AddonInstaller::getInstallName($package);
        $this->assertEquals($expected, $result);
    }

    public function testInstallerNameOverride(): void
    {
        $package = $this->createMockPackage('vendor/some-module', 'omeka-s-module', [
            'installer-name' => 'CustomName',
        ]);
        $result = AddonInstaller::getInstallName($package);
        $this->assertEquals('CustomName', $result);
    }

    public function moduleNameProvider(): array
    {
        return [
            ['vendor/value-suggest', 'ValueSuggest'],
            ['vendor/omeka-s-module-common', 'Common'],
            ['vendor/bulk-import-module', 'BulkImport'],
            ['vendor/module-lessonplans-omeka-s', 'Lessonplans'],
            ['vendor/neatline-omeka-s', 'Neatline'],
            ['daniel-km/omeka-s-module-common', 'Common'],
        ];
    }

    public function themeNameProvider(): array
    {
        return [
            ['vendor/thanks-roy', 'thanks-roy'],
            ['vendor/omeka-s-theme-repository', 'repository'],
            ['vendor/my-custom-theme-theme', 'my-custom-theme'],
            ['vendor/flavor-theme-omeka', 'flavor'],
        ];
    }

    protected function createMockPackage(string $name, string $type, array $extra = [])
    {
        $package = $this->createMock(\Composer\Package\PackageInterface::class);
        $package->method('getPrettyName')->willReturn($name);
        $package->method('getType')->willReturn($type);
        $package->method('getExtra')->willReturn($extra);
        return $package;
    }
}
