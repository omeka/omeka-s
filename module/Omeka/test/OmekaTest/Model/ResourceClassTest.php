<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\ResourceClass;

class ResourceClassTest extends \PHPUnit_Framework_TestCase
{
    protected $resourceClass;

    public function setUp()
    {
        $this->resourceClass = new ResourceClass;
    }

    public function testInitialState()
    {
        $this->assertNull($this->resourceClass->getId());
        $this->assertNull($this->resourceClass->getOwner());
        $this->assertNull($this->resourceClass->getVocabulary());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->resourceClass->getPropertyOverrides()
        );
        $this->assertNull($this->resourceClass->getLocalName());
        $this->assertNull($this->resourceClass->getLabel());
        $this->assertNull($this->resourceClass->getComment());
    }

    public function testSetState()
    {
        $this->resourceClass->setOwner('owner');
        $this->resourceClass->setVocabulary('vocabulary');
        $this->resourceClass->setLocalName('local_name');
        $this->resourceClass->setLabel('label');
        $this->resourceClass->setComment('comment');
        $this->assertEquals('owner', $this->resourceClass->getOwner());
        $this->assertEquals('vocabulary', $this->resourceClass->getVocabulary());
        $this->assertEquals('local_name', $this->resourceClass->getLocalName());
        $this->assertEquals('label', $this->resourceClass->getLabel());
        $this->assertEquals('comment', $this->resourceClass->getComment());
    }
}
