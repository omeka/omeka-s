<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\PropertyAssignment;
use Omeka\Model\Entity\PropertyAssignmentSet;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Model\Entity\User;
use Omeka\Test\TestCase;

class PropertyAssignmentSetTest extends TestCase
{
    protected $propertyAssignmentSet;

    public function setUp()
    {
        $this->propertyAssignmentSet = new PropertyAssignmentSet;
    }

    public function testInitialState()
    {
        $this->assertNull($this->propertyAssignmentSet->getId());
        $this->assertNull($this->propertyAssignmentSet->getLabel());
        $this->assertNull($this->propertyAssignmentSet->getOwner());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->propertyAssignmentSet->getPropertyAssignments()
        );
    }

    public function testSetLabel()
    {
        $label = 'test-label';
        $this->propertyAssignmentSet->setLabel($label);
        $this->assertEquals($label, $this->propertyAssignmentSet->getLabel());
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->propertyAssignmentSet->setOwner($owner);
        $this->assertSame($owner, $this->propertyAssignmentSet->getOwner());
        $this->assertTrue($owner->getPropertyAssignmentSets()->contains($this->propertyAssignmentSet));
    }

    public function testAddPropertyAssignment()
    {
        $propertyAssignment = new PropertyAssignment;
        $this->propertyAssignmentSet->addPropertyAssignment($propertyAssignment);
        $this->assertSame($this->propertyAssignmentSet, $propertyAssignment->getPropertyAssignmentSet());
        $this->assertTrue($this->propertyAssignmentSet->getPropertyAssignments()->contains($propertyAssignment));
    }

    public function testRemovePropertyAssignment()
    {
        $propertyAssignment = new PropertyAssignment;
        $this->propertyAssignmentSet->addPropertyAssignment($propertyAssignment);
        $this->propertyAssignmentSet->removePropertyAssignment($propertyAssignment);
        $this->assertFalse($this->propertyAssignmentSet->getPropertyAssignments()->contains($propertyAssignment));
        $this->assertNull($propertyAssignment->getPropertyAssignmentSet());
    }
}
