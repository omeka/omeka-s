<?php
namespace OmekaTest\File;

use Omeka\File\Exception\RuntimeException;
use Omeka\File\StagedFiles;
use Omeka\Test\DbTestCase;

class StagedFilesTest extends DbTestCase
{
    /**
     * @var StagedFiles
     */
    protected $stagedFiles;

    public function setUp(): void
    {
        parent::setUp();
        $this->stagedFiles = self::getApplication()->getServiceManager()->get('Omeka\File\StagedFiles');
    }

    public function testStagesACopyUntilDeleted()
    {
        $file = tempnam(sys_get_temp_dir(), 'omeka-test-');
        file_put_contents($file, 'Some text');
        $token = $this->stagedFiles->stage($file);
        $path = $this->stagedFiles->getPath($token);
        // A copy, in the temporary directory, which the original can outlast.
        $this->assertNotSame($file, $path);
        $this->assertStringStartsWith(StagedFiles::FILE_PREFIX, basename($path));
        $this->assertFileEquals($file, $path);
        unlink($file);
        $this->assertFileExists($path);

        $this->stagedFiles->delete($token);
        $this->assertNull($this->stagedFiles->getPath($token));
        $this->assertFileDoesNotExist($path);
    }

    public function testStagesContents()
    {
        $token = $this->stagedFiles->stageContents('Some text');
        $path = $this->stagedFiles->getPath($token);
        $this->assertStringStartsWith(StagedFiles::FILE_PREFIX, basename($path));
        $this->assertStringEqualsFile($path, 'Some text');
        $this->stagedFiles->delete($token);
    }

    public function testATokenItDidNotGiveFindsNoFile()
    {
        $this->assertNull($this->stagedFiles->getPath(bin2hex(random_bytes(16))));
        // Deleting one does nothing.
        $this->stagedFiles->delete('unknown');
        $this->assertNull($this->stagedFiles->getPath('unknown'));
    }

    public function testAFileThatCantBeReadIsRefused()
    {
        $this->expectException(RuntimeException::class);
        $this->stagedFiles->stage(__DIR__ . '/missing.txt');
    }
}
