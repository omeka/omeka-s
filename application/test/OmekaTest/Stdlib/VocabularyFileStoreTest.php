<?php
namespace OmekaTest\Stdlib;

use Omeka\Api\Exception\ValidationException;
use Omeka\Stdlib\VocabularyFileStore;
use Omeka\Test\DbTestCase;

class VocabularyFileStoreTest extends DbTestCase
{
    /**
     * @var VocabularyFileStore
     */
    protected $store;

    public function setUp(): void
    {
        parent::setUp();
        $this->store = self::getApplication()->getServiceManager()->get('Omeka\VocabularyFileStore');
    }

    public function testKeepsACopyUntilRemoved()
    {
        $token = $this->store->keep(__DIR__ . '/_files/compare.ttl');
        $path = $this->store->getPath($token);
        // A copy, in the temporary directory, which the original can outlast.
        $this->assertNotSame(__DIR__ . '/_files/compare.ttl', $path);
        $this->assertStringStartsWith(VocabularyFileStore::FILE_PREFIX, basename($path));
        $this->assertFileEquals(__DIR__ . '/_files/compare.ttl', $path);

        $this->store->remove($token);
        $this->assertNull($this->store->getPath($token));
        $this->assertFileDoesNotExist($path);
    }

    public function testATokenItDidNotGiveFindsNoFile()
    {
        $this->assertNull($this->store->getPath(bin2hex(random_bytes(16))));
        // Removing one does nothing.
        $this->store->remove('unknown');
        $this->assertNull($this->store->getPath('unknown'));
    }

    public function testAFileThatCantBeReadIsRefused()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not read vocabulary file.');
        $this->store->keep(__DIR__ . '/_files/missing.ttl');
    }
}
