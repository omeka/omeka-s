<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Model\Entity\User;
use Omeka\Model\Entity\Vocabulary;
use Omeka\Test\TestCase;

class VocabularyTest extends TestCase
{
    protected $vocabulary;

    public function setUp()
    {
        $this->vocabulary = new Vocabulary;
    }

    public function testInitialState()
    {
        $this->assertNull($this->vocabulary->getId());
        $this->assertNull($this->vocabulary->getOwner());
        $this->assertNull($this->vocabulary->getNamespaceUri());
        $this->assertNull($this->vocabulary->getPrefix());
        $this->assertNull($this->vocabulary->getLabel());
        $this->assertNull($this->vocabulary->getComment());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->vocabulary->getResourceClasses()
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->vocabulary->getProperties()
        );
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->vocabulary->setOwner($owner);
        $this->assertSame($owner, $this->vocabulary->getOwner());
        $this->assertTrue($owner->getVocabularies()->contains($this->vocabulary));
    }

    public function testSetNamespaceUri()
    {
        $namespaceUri = 'test-namespaceUri';
        $this->vocabulary->setNamespaceUri($namespaceUri);
        $this->assertEquals($namespaceUri, $this->vocabulary->getNamespaceUri());
    }

    public function testSetPrefix()
    {
        $prefix = 'test-prefix';
        $this->vocabulary->setPrefix($prefix);
        $this->assertEquals($prefix, $this->vocabulary->getPrefix());
    }

    public function testSetlabel()
    {
        $label = 'test-label';
        $this->vocabulary->setLabel($label);
        $this->assertEquals($label, $this->vocabulary->getLabel());
    }

    public function testSetComment()
    {
        $comment = 'test-comment';
        $this->vocabulary->setComment($comment);
        $this->assertEquals($comment, $this->vocabulary->getComment());
    }

    public function testAddResourceClass()
    {
        $resourceClass = new ResourceClass;
        $this->vocabulary->addResourceClass($resourceClass);
        $this->assertSame($this->vocabulary, $resourceClass->getVocabulary());
        $this->assertTrue($this->vocabulary->getResourceClasses()->contains($resourceClass));
    }

    public function testRemoveResourceClass()
    {
        $resourceClass = new ResourceClass;
        $this->vocabulary->addResourceClass($resourceClass);
        $this->vocabulary->removeResourceClass($resourceClass);
        $this->assertFalse($this->vocabulary->getResourceClasses()->contains($resourceClass));
        $this->assertNull($resourceClass->getVocabulary());
    }

    public function testAddProperty()
    {
        $property = new Property;
        $this->vocabulary->addProperty($property);
        $this->assertSame($this->vocabulary, $property->getVocabulary());
        $this->assertTrue($this->vocabulary->getProperties()->contains($property));
    }

    public function testRemoveProperty()
    {
        $property = new Property;
        $this->vocabulary->addProperty($property);
        $this->vocabulary->removeProperty($property);
        $this->assertFalse($this->vocabulary->getProperties()->contains($property));
        $this->assertNull($property->getVocabulary());
    }
}
