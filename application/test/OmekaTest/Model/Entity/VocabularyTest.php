<?php
namespace OmekaTest\Model;

use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
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
}
