<?php
namespace OmekaTest\File;

use Omeka\File\Exception\RuntimeException;
use Omeka\File\Stager;
use Omeka\Test\DbTestCase;

class StagerTest extends DbTestCase
{
    /**
     * @var Stager
     */
    protected $stager;

    public function setUp(): void
    {
        parent::setUp();
        $this->stager = self::getApplication()->getServiceManager()->get('Omeka\File\Stager');
    }

    public function testStagesACopy()
    {
        $file = tempnam(sys_get_temp_dir(), 'omeka-test-');
        file_put_contents($file, 'Some text');
        $path = $this->stager->stage($file);
        // A copy, in the temporary directory, which outlasts the original.
        $this->assertNotSame($file, $path);
        $this->assertStringStartsWith(Stager::FILE_PREFIX, basename($path));
        $this->assertFileEquals($file, $path);
        unlink($file);
        $this->assertFileExists($path);
        unlink($path);
    }

    public function testStagesContents()
    {
        $path = $this->stager->stageContents('Some text');
        $this->assertStringStartsWith(Stager::FILE_PREFIX, basename($path));
        $this->assertStringEqualsFile($path, 'Some text');
        unlink($path);
    }

    public function testAFileThatCantBeReadIsRefused()
    {
        $this->expectException(RuntimeException::class);
        $this->stager->stage(__DIR__ . '/missing.txt');
    }
}
