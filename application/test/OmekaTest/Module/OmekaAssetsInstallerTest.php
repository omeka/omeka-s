<?php declare(strict_types=1);

namespace OmekaTest\Module;

use Omeka\Module\OmekaAssetsInstaller;
use Omeka\Test\TestCase;

/**
 * Test OmekaAssetsInstaller functionality.
 *
 * Note: These tests verify the logic without making actual HTTP requests.
 */
class OmekaAssetsInstallerTest extends TestCase
{
    protected function getInstaller(): OmekaAssetsInstaller
    {
        $logger = $this->createMock(\Laminas\Log\LoggerInterface::class);
        return new OmekaAssetsInstaller($logger);
    }

    public function testInstallFromPathWithNoComposerJson(): void
    {
        $installer = $this->getInstaller();
        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);

        // No composer.json, should return true (nothing to do)
        $result = $installer->installFromPath($tempDir);
        $this->assertTrue($result);

        rmdir($tempDir);
    }

    public function testInstallFromPathWithEmptyOmekaAssets(): void
    {
        $installer = $this->getInstaller();
        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);

        // Create composer.json without omeka-assets
        file_put_contents($tempDir . '/composer.json', json_encode([
            'name' => 'test/module',
            'extra' => [],
        ]));

        $result = $installer->installFromPath($tempDir);
        $this->assertTrue($result);

        unlink($tempDir . '/composer.json');
        rmdir($tempDir);
    }

    public function testInstallFromPathWithExistingAsset(): void
    {
        $installer = $this->getInstaller();
        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/asset/vendor', 0755, true);

        // Create a file that already exists
        file_put_contents($tempDir . '/asset/vendor/file.js', 'existing');

        // Create composer.json with omeka-assets pointing to existing file
        file_put_contents($tempDir . '/composer.json', json_encode([
            'name' => 'test/module',
            'extra' => [
                'omeka-assets' => [
                    'asset/vendor/file.js' => 'https://example.com/file.js',
                ],
            ],
        ]));

        // Should return true (asset exists, skip download)
        $result = $installer->installFromPath($tempDir);
        $this->assertTrue($result);

        // Verify the file was NOT overwritten (still has original content)
        $this->assertEquals('existing', file_get_contents($tempDir . '/asset/vendor/file.js'));

        // Cleanup
        unlink($tempDir . '/asset/vendor/file.js');
        unlink($tempDir . '/composer.json');
        rmdir($tempDir . '/asset/vendor');
        rmdir($tempDir . '/asset');
        rmdir($tempDir);
    }

    public function testInstallFromPathWithExistingDirectory(): void
    {
        $installer = $this->getInstaller();
        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/asset/vendor/lib', 0755, true);

        // Create a file in the directory to mark it as non-empty
        file_put_contents($tempDir . '/asset/vendor/lib/existing.js', 'content');

        // Create composer.json with omeka-assets pointing to existing directory
        file_put_contents($tempDir . '/composer.json', json_encode([
            'name' => 'test/module',
            'extra' => [
                'omeka-assets' => [
                    'asset/vendor/lib/' => 'https://example.com/archive.zip',
                ],
            ],
        ]));

        // Should return true (directory not empty, skip download)
        $result = $installer->installFromPath($tempDir);
        $this->assertTrue($result);

        // Cleanup
        unlink($tempDir . '/asset/vendor/lib/existing.js');
        unlink($tempDir . '/composer.json');
        rmdir($tempDir . '/asset/vendor/lib');
        rmdir($tempDir . '/asset/vendor');
        rmdir($tempDir . '/asset');
        rmdir($tempDir);
    }

    public function testAssetExistsForFile(): void
    {
        $installer = $this->getInstaller();

        // Use reflection to test protected method
        $method = new \ReflectionMethod($installer, 'assetExists');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);

        // Non-existent file
        $this->assertFalse($method->invoke($installer, $tempDir . '/file.js', 'file.js'));

        // Existent file
        file_put_contents($tempDir . '/file.js', 'content');
        $this->assertTrue($method->invoke($installer, $tempDir . '/file.js', 'file.js'));

        // Cleanup
        unlink($tempDir . '/file.js');
        rmdir($tempDir);
    }

    public function testAssetExistsForDirectory(): void
    {
        $installer = $this->getInstaller();

        // Use reflection to test protected method
        $method = new \ReflectionMethod($installer, 'assetExists');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);

        // Non-existent directory
        $this->assertFalse($method->invoke($installer, $tempDir . '/lib/', 'lib/'));

        // Empty directory (should be considered as not existing)
        mkdir($tempDir . '/lib');
        $this->assertFalse($method->invoke($installer, $tempDir . '/lib/', 'lib/'));

        // Non-empty directory
        file_put_contents($tempDir . '/lib/file.js', 'content');
        $this->assertTrue($method->invoke($installer, $tempDir . '/lib/', 'lib/'));

        // Cleanup
        unlink($tempDir . '/lib/file.js');
        rmdir($tempDir . '/lib');
        rmdir($tempDir);
    }

    public function testArchiveSourceDirWithSingleRootDirectory(): void
    {
        $installer = $this->getInstaller();

        // Use reflection to test protected method
        $method = new \ReflectionMethod($installer, 'getArchiveSourceDir');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/mirador-2.7.0');
        touch($tempDir . '/mirador-2.7.0/file1.js');

        // Single root directory should return that directory (for stripping)
        $result = $method->invoke($installer, $tempDir);
        $this->assertEquals($tempDir . '/mirador-2.7.0', $result);

        // Cleanup
        unlink($tempDir . '/mirador-2.7.0/file1.js');
        rmdir($tempDir . '/mirador-2.7.0');
        rmdir($tempDir);
    }

    public function testArchiveSourceDirWithMultipleEntries(): void
    {
        $installer = $this->getInstaller();

        // Use reflection to test protected method
        $method = new \ReflectionMethod($installer, 'getArchiveSourceDir');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/file1.js');
        touch($tempDir . '/file2.js');

        // Multiple entries should return the tempDir itself
        $result = $method->invoke($installer, $tempDir);
        $this->assertEquals($tempDir, $result);

        // Cleanup
        unlink($tempDir . '/file1.js');
        unlink($tempDir . '/file2.js');
        rmdir($tempDir);
    }

    public function testMoveDirectoryContents(): void
    {
        $installer = $this->getInstaller();

        // Use reflection to test protected method
        $method = new \ReflectionMethod($installer, 'moveDirectoryContents');
        $method->setAccessible(true);

        $srcDir = sys_get_temp_dir() . '/omeka_test_src_' . uniqid();
        $dstDir = sys_get_temp_dir() . '/omeka_test_dst_' . uniqid();

        mkdir($srcDir);
        mkdir($srcDir . '/subdir');
        file_put_contents($srcDir . '/file1.js', 'content1');
        file_put_contents($srcDir . '/subdir/file2.js', 'content2');

        mkdir($dstDir);

        $method->invoke($installer, $srcDir, $dstDir);

        // Check files were moved
        $this->assertFileExists($dstDir . '/file1.js');
        $this->assertFileExists($dstDir . '/subdir/file2.js');
        $this->assertEquals('content1', file_get_contents($dstDir . '/file1.js'));
        $this->assertEquals('content2', file_get_contents($dstDir . '/subdir/file2.js'));

        // Cleanup
        unlink($dstDir . '/file1.js');
        unlink($dstDir . '/subdir/file2.js');
        rmdir($dstDir . '/subdir');
        rmdir($dstDir);
        // srcDir should be empty now (files moved)
        @rmdir($srcDir . '/subdir');
        @rmdir($srcDir);
    }
}
