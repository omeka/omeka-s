<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Media;
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
        $this->assertNull($this->media->getOwner());
        $this->assertNull($this->media->getResourceClass());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->media->getSites()
        );
        $this->assertNull($this->media->getItem());
        $this->assertNull($this->media->getType());
        $this->assertNull($this->media->getData());
        $this->assertNull($this->media->getFile());
    }

    public function testSetState()
    {
        $this->media->setOwner('owner');
        $this->media->setResourceClass('resource_class');
        $this->media->setItem('item');
        $this->media->setType('type');
        $this->media->setData('data');
        $this->media->setFile('file');
        $this->assertEquals('owner', $this->media->getOwner());
        $this->assertEquals('resource_class', $this->media->getResourceClass());
        $this->assertEquals('item', $this->media->getItem());
        $this->assertEquals('type', $this->media->getType());
        $this->assertEquals('data', $this->media->getData());
        $this->assertEquals('file', $this->media->getFile());
    }
}
