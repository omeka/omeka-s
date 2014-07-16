<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\PropertyAssignment;
use Omeka\Model\Entity\PropertyAssignmentSet;
use Omeka\Test\TestCase;

class PropertyAssignmentTest extends TestCase
{
    protected $propertyAssignment;

    public function setUp()
    {
        $this->propertyAssignment = new PropertyAssignment;
    }

    public function testInitialState()
    {
        $this->assertNull($this->propertyAssignment->getId());
        $this->assertNull($this->propertyAssignment->getPropertyAssignmentSet());
        $this->assertNull($this->propertyAssignment->getProperty());
        $this->assertNull($this->propertyAssignment->getAlternateLabel());
        $this->assertNull($this->propertyAssignment->getAlternateComment());
        $this->assertTrue($this->propertyAssignment->isDefault());
    }

    public function testSetPropertyAssignmentSet()
    {
        $propertyAssignmentSet = new PropertyAssignmentSet;
        $this->propertyAssignment->setPropertyAssignmentSet($propertyAssignmentSet);
        $this->assertSame($propertyAssignmentSet, $this->propertyAssignment->getPropertyAssignmentSet());
        $this->assertTrue($propertyAssignmentSet->getPropertyAssignments()->contains($this->propertyAssignment));
    }

    public function testSetProperty()
    {
        $property = new Property;
        $this->propertyAssignment->setProperty($property);
        $this->assertSame($property, $this->propertyAssignment->getProperty());
    }

    public function testSetAlternateLabel()
    {
        $alternateLabel = 'test-alternateLabel';
        $this->propertyAssignment->setAlternateLabel($alternateLabel);
        $this->assertEquals($alternateLabel, $this->propertyAssignment->getAlternateLabel());
    }

    public function testSetAlternateComment()
    {
        $alternateComment = 'test-alternateComment';
        $this->propertyAssignment->setAlternateComment($alternateComment);
        $this->assertEquals($alternateComment, $this->propertyAssignment->getAlternateComment());
    }

    public function setIsDefault()
    {
        $isDefault = false;
        $this->propertyAssignment->setIsDefault($isDefault);
        $this->assertFalse($this->propertyAssignment->isDefault());
    }
}
