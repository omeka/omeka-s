<?php
namespace OmekaTest\Model;

use Omeka\Entity\ResourceClass;
use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\Test\TestCase;

class ResourceClassTest extends TestCase
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
        $this->assertNull($this->resourceClass->getLocalName());
        $this->assertNull($this->resourceClass->getLabel());
        $this->assertNull($this->resourceClass->getComment());
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->resourceClass->setOwner($owner);
        $this->assertSame($owner, $this->resourceClass->getOwner());
    }

    public function testSetVocabulary()
    {
        $vocabulary = new Vocabulary;
        $this->resourceClass->setVocabulary($vocabulary);
        $this->assertSame($vocabulary, $this->resourceClass->getVocabulary());
    }

    public function testSetLocalName()
    {
        $localName = 'test-localName';
        $this->resourceClass->setLocalName($localName);
        $this->assertEquals($localName, $this->resourceClass->getLocalName());
    }

    public function testSetLabel()
    {
        $label = 'test-label';
        $this->resourceClass->setLabel($label);
        $this->assertEquals($label, $this->resourceClass->getLabel());
    }

    public function testSetComment()
    {
        $comment = 'test-comment';
        $this->resourceClass->setComment($comment);
        $this->assertEquals($comment, $this->resourceClass->getComment());
    }
}
