<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\ResourceClass;
use Omeka\Model\Entity\User;
use Omeka\Model\Entity\Value;
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
            'Doctrine\Common\Collections\ArrayCollection',
            $this->resource->getSites()
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->resource->getValues()
        );
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->resource->setOwner($owner);
        $this->assertSame($owner, $this->resource->getOwner());
    }

    public function testSetResourceClass()
    {
        $resourceClass = new ResourceClass;
        $this->resource->setResourceClass($resourceClass);
        $this->assertSame($resourceClass, $this->resource->getResourceClass());
    }

    public function testAddValue()
    {
        $value = new Value;
        $this->resource->addValue($value);
        $this->assertSame($this->resource, $value->getResource());
        $this->assertTrue($this->resource->getValues()->contains($value));
    }

    public function testRemoveValue()
    {
        $value = new Value;
        $this->resource->addValue($value);
        $this->assertTrue($this->resource->removeValue($value));
        $this->assertNull($value->getValue());
    }
}
