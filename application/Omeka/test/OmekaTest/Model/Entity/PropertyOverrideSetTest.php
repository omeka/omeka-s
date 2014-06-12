<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\PropertyOverrideSet;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Model\Entity\User;
use Omeka\Test\TestCase;

class PropertyOverrideSetTest extends TestCase
{
    protected $propertyOverrideSet;

    public function setUp()
    {
        $this->propertyOverrideSet = new PropertyOverrideSet;
    }

    public function testInitialState()
    {
        $this->assertNull($this->propertyOverrideSet->getId());
        $this->assertNull($this->propertyOverrideSet->getLabel());
        $this->assertNull($this->propertyOverrideSet->getResourceClass());
        $this->assertNull($this->propertyOverrideSet->getOwner());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->propertyOverrideSet->getPropertyOverrides()
        );
    }

    public function testSetLabel()
    {
        $label = 'test-label';
        $this->propertyOverrideSet->setLabel($label);
        $this->assertEquals($label, $this->propertyOverrideSet->getLabel());
    }

    public function testSetResourceClass()
    {
        $resourceClass = new ResourceClass;
        $this->propertyOverrideSet->setResourceClass($resourceClass);
        $this->assertSame($resourceClass, $this->propertyOverrideSet->getResourceClass());
        $this->assertTrue($resourceClass->getPropertyOverrideSets()->contains($this->propertyOverrideSet));
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->propertyOverrideSet->setOwner($owner);
        $this->assertSame($owner, $this->propertyOverrideSet->getOwner());
    }
}
