<?php
namespace OmekaTest\Model;

use DateTime;
use Omeka\Entity\ResourceTemplate;
use Omeka\Entity\ResourceClass;
use Omeka\Entity\User;
use Omeka\Test\TestCase;

class ResourceTest extends TestCase
{
    protected $resource;

    public function setUp()
    {
        $this->resource = $this->getMockForAbstractClass('Omeka\Entity\Resource');
    }

    public function testInitialState()
    {
        $this->assertNull($this->resource->getId());
        $this->assertNull($this->resource->getOwner());
        $this->assertNull($this->resource->getResourceClass());
        $this->assertNull($this->resource->getResourceTemplate());
        $this->assertNull($this->resource->getCreated());
        $this->assertNull($this->resource->getModified());
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

    public function testSetResourceTemplate()
    {
        $resourceTemplate = new ResourceTemplate;
        $this->resource->setResourceTemplate($resourceTemplate);
        $this->assertSame($resourceTemplate, $this->resource->getResourceTemplate());
    }

    public function testSetCreated()
    {
        $dateTime = new DateTime;
        $this->resource->setCreated($dateTime);
        $this->assertSame($dateTime, $this->resource->getCreated());
    }

    public function testSetModified()
    {
        $dateTime = new DateTime;
        $this->resource->setModified($dateTime);
        $this->assertSame($dateTime, $this->resource->getModified());
    }
}
