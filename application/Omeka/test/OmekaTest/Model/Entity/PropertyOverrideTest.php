<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\PropertyOverride;
use Omeka\Model\Entity\PropertyOverrideSet;
use Omeka\Test\TestCase;

class PropertyOverrideTest extends TestCase
{
    protected $propertyOverride;

    public function setUp()
    {
        $this->propertyOverride = new PropertyOverride;
    }

    public function testInitialState()
    {
        $this->assertNull($this->propertyOverride->getId());
        $this->assertNull($this->propertyOverride->getPropertyOverrideSet());
        $this->assertNull($this->propertyOverride->getProperty());
        $this->assertNull($this->propertyOverride->getAlternateLabel());
        $this->assertNull($this->propertyOverride->getAlternateComment());
        $this->assertTrue($this->propertyOverride->isDefault());
    }

    public function testSetPropertyOverrideSet()
    {
        $propertyOverrideSet = new PropertyOverrideSet;
        $this->propertyOverride->setPropertyOverrideSet($propertyOverrideSet);
        $this->assertSame($propertyOverrideSet, $this->propertyOverride->getPropertyOverrideSet());
    }

    public function testSetProperty()
    {
        $property = new Property;
        $this->propertyOverride->setProperty($property);
        $this->assertSame($property, $this->propertyOverride->getProperty());
    }

    public function testSetAlternateLabel()
    {
        $alternateLabel = 'test-alternateLabel';
        $this->propertyOverride->setAlternateLabel($alternateLabel);
        $this->assertEquals($alternateLabel, $this->propertyOverride->getAlternateLabel());
    }

    public function testSetAlternateComment()
    {
        $alternateComment = 'test-alternateComment';
        $this->propertyOverride->setAlternateComment($alternateComment);
        $this->assertEquals($alternateComment, $this->propertyOverride->getAlternateComment());
    }

    public function setIsDefault()
    {
        $isDefault = false;
        $this->propertyOverride->setIsDefault($isDefault);
        $this->assertFalse($this->propertyOverride->isDefault());
    }
}
