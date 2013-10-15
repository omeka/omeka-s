<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Vocabulary;

class VocabularyTest extends \PHPUnit_Framework_TestCase
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
        $this->assertNull($this->vocabulary->getLabel());
        $this->assertNull($this->vocabulary->getComment());
    }

    public function testSetState()
    {
        $this->vocabulary->setOwner('owner');
        $this->vocabulary->setNamespaceUri('namespace_uri');
        $this->vocabulary->setLabel('label');
        $this->vocabulary->setComment('comment');
        $this->assertEquals('owner', $this->vocabulary->getOwner());
        $this->assertEquals('namespace_uri', $this->vocabulary->getNamespaceUri());
        $this->assertEquals('label', $this->vocabulary->getLabel());
        $this->assertEquals('comment', $this->vocabulary->getComment());
    }
}
