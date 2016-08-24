<?php
namespace OmekaTest\Model;

use Omeka\Entity\Item;
use Omeka\Entity\Media;
use Omeka\Test\TestCase;

class MediaTest extends TestCase
{
    protected $media;

    public function setUp()
    {
        $this->media = new Media;
    }

    public function testInitialState()
    {
        $this->assertNull($this->media->getId());
        $this->assertNull($this->media->getIngester());
        $this->assertNull($this->media->getRenderer());
        $this->assertNull($this->media->getData());
        $this->assertTrue($this->media->isPublic());
        $this->assertNull($this->media->getFilename());
        $this->assertNull($this->media->getStorageId());
        $this->assertNull($this->media->getExtension());
        $this->assertNull($this->media->getSource());
        $this->assertNull($this->media->getItem());
    }

    public function testSetData()
    {
        $data = 'test-data';
        $this->media->setData($data);
        $this->assertEquals($data, $this->media->getData());
    }

    public function testSetIsPublic()
    {
        $this->media->setIsPublic(true);
        $this->assertTrue($this->media->isPublic());
    }

    public function testSetFilename()
    {
        $storageId = 'foo';
        $this->media->setStorageId($storageId);
        $this->assertEquals($storageId, $this->media->getStorageId());

        $extension = 'jpg';
        $this->media->setExtension($extension);
        $this->assertEquals($extension, $this->media->getExtension());

        $this->assertEquals("$storageId.$extension", $this->media->getFilename());
    }

    public function testSetSource()
    {
        $source = 'http://example.com/foo.jpg';
        $this->media->setSource($source);
        $this->assertEquals($source, $this->media->getSource());
    }

    public function testSetItem()
    {
        $item = new Item;
        $this->media->setItem($item);
        $this->assertSame($item, $this->media->getItem());
    }
}
