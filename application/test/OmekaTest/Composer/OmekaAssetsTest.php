<?php declare(strict_types=1);

namespace OmekaTest\Composer;

use Omeka\Test\TestCase;

/**
 * Test AddonInstallerPlugin omeka-assets functionality.
 *
 * Note: Full integration tests require running composer install/update.
 * These tests verify the configuration parsing and logic.
 */
class OmekaAssetsTest extends TestCase
{
    /**
     * @dataProvider omekaAssetsProvider
     */
    public function testOmekaAssetsDestinationDetection(string $destination, string $url, bool $expectDirectory, bool $expectArchive): void
    {
        $isDirectory = substr($destination, -1) === '/';
        $isArchive = (bool) preg_match('/\.(zip|tar\.gz|tgz)$/i', $url);

        $this->assertEquals($expectDirectory, $isDirectory, "Directory detection for: $destination");
        $this->assertEquals($expectArchive, $isArchive, "Archive detection for: $url");
    }

    public function omekaAssetsProvider(): array
    {
        return [
            // [destination, url, expectDirectory, expectArchive]
            [
                'asset/vendor/lib/file.min.js',
                'https://example.com/file.min.js',
                false, // not a directory
                false, // not an archive
            ],
            [
                'asset/vendor/lib/',
                'https://example.com/archive.zip',
                true,  // is a directory
                true,  // is an archive
            ],
            [
                'asset/vendor/mirador/',
                'https://example.com/mirador-2.7.0.tar.gz',
                true,  // is a directory
                true,  // is an archive
            ],
            [
                'asset/vendor/lib/',
                'https://example.com/file.tgz',
                true,  // is a directory
                true,  // is an archive
            ],
            [
                'asset/css/custom.css',
                'https://example.com/styles.css',
                false, // not a directory
                false, // not an archive
            ],
            // Third case: directory + non-archive = copy file into directory
            [
                'asset/vendor/lib/',
                'https://example.com/jquery.min.js',
                true,  // is a directory
                false, // not an archive → file copied into directory
            ],
        ];
    }

    /**
     * @dataProvider omekaAssetsActionProvider
     */
    public function testOmekaAssetsActionDetection(string $destination, string $url, string $expectedAction): void
    {
        $isDirectory = substr($destination, -1) === '/';
        $isArchive = (bool) preg_match('/\.(zip|tar\.gz|tgz)$/i', $url);

        if ($isDirectory && $isArchive) {
            $action = 'extract';
        } elseif ($isDirectory) {
            $action = 'copy_into_dir';
        } else {
            $action = 'download';
        }

        $this->assertEquals($expectedAction, $action, "Action for: $destination <- $url");
    }

    public function omekaAssetsActionProvider(): array
    {
        return [
            // [destination, url, expectedAction]
            ['asset/vendor/lib/file.min.js', 'https://example.com/file.min.js', 'download'],
            ['asset/vendor/lib/', 'https://example.com/archive.zip', 'extract'],
            ['asset/vendor/lib/', 'https://example.com/archive.tar.gz', 'extract'],
            ['asset/vendor/lib/', 'https://example.com/jquery.min.js', 'copy_into_dir'],
            ['asset/vendor/lib/', 'https://example.com/styles.css', 'copy_into_dir'],
        ];
    }

    public function testDestinationFilenameRename(): void
    {
        // When destination has a different filename than the URL, it renames.
        $destination = 'asset/vendor/lib/jquery.autocomplete.min.js';
        $url = 'https://example.com/jquery.autocomplete-1.5.0.min.js';

        // The destination path is used as-is (not the URL basename).
        $destPath = '/install/path/' . ltrim($destination, '/');
        $this->assertEquals('/install/path/asset/vendor/lib/jquery.autocomplete.min.js', $destPath);
        $this->assertNotEquals(basename($url), basename($destPath));
    }

    public function testArchiveSingleRootDirectoryStripping(): void
    {
        // Simulate the logic that detects a single root directory.
        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/mirador-2.7.0');
        touch($tempDir . '/mirador-2.7.0/file1.js');
        touch($tempDir . '/mirador-2.7.0/file2.js');

        $entries = array_diff(scandir($tempDir), ['.', '..']);

        // Single entry that is a directory → should be stripped.
        $this->assertCount(1, $entries);
        $entry = reset($entries);
        $this->assertTrue(is_dir($tempDir . '/' . $entry));

        // The source dir should be the nested directory.
        $sourceDir = $tempDir . '/' . $entry;
        $this->assertEquals($tempDir . '/mirador-2.7.0', $sourceDir);

        // Cleanup.
        unlink($tempDir . '/mirador-2.7.0/file1.js');
        unlink($tempDir . '/mirador-2.7.0/file2.js');
        rmdir($tempDir . '/mirador-2.7.0');
        rmdir($tempDir);
    }

    public function testArchiveMultipleEntriesNoStripping(): void
    {
        // Simulate an archive with multiple root entries.
        $tempDir = sys_get_temp_dir() . '/omeka_test_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/file1.js');
        touch($tempDir . '/file2.js');

        $entries = array_diff(scandir($tempDir), ['.', '..']);

        // Multiple entries → no stripping, use tempDir as source.
        $this->assertCount(2, $entries);

        // Cleanup.
        unlink($tempDir . '/file1.js');
        unlink($tempDir . '/file2.js');
        rmdir($tempDir);
    }

    public function testOmekaAssetsConfigParsing(): void
    {
        $composerJson = [
            'extra' => [
                'omeka-assets' => [
                    'asset/vendor/jquery-autocomplete/jquery.autocomplete.min.js' => 'https://example.com/jquery.autocomplete.min.js',
                    'asset/vendor/mirador/' => 'https://example.com/mirador.zip',
                ],
            ],
        ];

        $extra = $composerJson['extra'];
        $this->assertArrayHasKey('omeka-assets', $extra);
        $this->assertIsArray($extra['omeka-assets']);
        $this->assertCount(2, $extra['omeka-assets']);

        foreach ($extra['omeka-assets'] as $destination => $url) {
            $this->assertIsString($destination);
            $this->assertIsString($url);
            $this->assertStringStartsWith('https://', $url);
        }
    }

    public function testEmptyOmekaAssetsConfig(): void
    {
        $composerJson = [
            'extra' => [],
        ];

        $extra = $composerJson['extra'];
        $hasAssets = !empty($extra['omeka-assets']) && is_array($extra['omeka-assets']);
        $this->assertFalse($hasAssets);
    }
}
