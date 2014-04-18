<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Resource;
use Omeka\Test\TestCase;

class ResourceTest extends TestCase
{
    protected $resource;

    public function setUp()
    {
        $this->resource = $this->getMockForAbstractClass('Omeka\Model\Entity\Resource');
    }

    public function testInitialState()
    {
        $this->assertNull($this->resource->getId());
        $this->assertNull($this->resource->getOwner());
        $this->assertNull($this->resource->getResourceClass());
        $this->assertInstanceOf(
            '\Doctrine\Common\Collections\ArrayCollection',
            $this->resource->getSites()
        );
    }

    public function testSetState()
    {
        $this->resource->setOwner('owner');
        $this->resource->setResourceClass('resource_class');
        $this->assertEquals('owner', $this->resource->getOwner());
        $this->assertEquals('resource_class', $this->resource->getResourceClass());
    }
}
