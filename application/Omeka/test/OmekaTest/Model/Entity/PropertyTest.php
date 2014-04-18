<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Property;
use Omeka\Test\TestCase;

class PropertyTest extends TestCase
{
    protected $property;

    public function setUp()
    {
        $this->property = new Property;
    }

    public function testInitialState()
    {
        $this->assertNull($this->property->getId());
        $this->assertNull($this->property->getOwner());
        $this->assertNull($this->property->getVocabulary());
        $this->assertNull($this->property->getLocalName());
        $this->assertNull($this->property->getLabel());
        $this->assertNull($this->property->getComment());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->property->getValues()
        );
    }

    public function testSetState()
    {
        $this->property->setOwner('owner');
        $this->property->setVocabulary('vocabulary');
        $this->property->setLocalName('local_name');
        $this->property->setLabel('label');
        $this->property->setComment('comment');
        $this->assertEquals('owner', $this->property->getOwner());
        $this->assertEquals('vocabulary', $this->property->getVocabulary());
        $this->assertEquals('local_name', $this->property->getLocalName());
        $this->assertEquals('label', $this->property->getLabel());
        $this->assertEquals('comment', $this->property->getComment());
    }
}
