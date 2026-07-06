<?php
namespace OmekaTest\Stdlib;

use Omeka\Stdlib\SecretKey;
use Omeka\Test\TestCase;

class SecretKeyTest extends TestCase
{
    private $dir;

    public function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/omeka-secretkey-' . uniqid();
        mkdir($this->dir);
        putenv(SecretKey::ENV);
    }

    public function tearDown(): void
    {
        @unlink($this->dir . '/' . SecretKey::FILE);
        @rmdir($this->dir);
        putenv(SecretKey::ENV);
    }

    public function testGenerateIsBase64Of32RandomBytes()
    {
        $key = SecretKey::generate();
        $this->assertSame(32, strlen((string) base64_decode($key, true)));
        $this->assertNotSame(SecretKey::generate(), SecretKey::generate());
    }

    public function testResolveReturnsNullWhenNothingIsSet()
    {
        $this->assertNull(SecretKey::resolve($this->dir));
    }

    public function testStoreThenResolveRoundTrip()
    {
        $key = SecretKey::generate();
        $this->assertTrue(SecretKey::store($key, $this->dir));
        $this->assertSame($key, SecretKey::resolve($this->dir));
    }

    public function testFileTakesPrecedenceOverEnvironment()
    {
        $fileKey = SecretKey::generate();
        SecretKey::store($fileKey, $this->dir);
        putenv(SecretKey::ENV . '=env-key');
        $this->assertSame($fileKey, SecretKey::resolve($this->dir));
    }

    public function testEnvironmentIsUsedWhenThereIsNoFile()
    {
        putenv(SecretKey::ENV . '=env-key');
        $this->assertSame('env-key', SecretKey::resolve($this->dir));
    }

    public function testStoreReturnsFalseWhenDirectoryIsNotWritable()
    {
        $this->assertFalse(SecretKey::store(SecretKey::generate(), $this->dir . '/missing'));
    }
}
