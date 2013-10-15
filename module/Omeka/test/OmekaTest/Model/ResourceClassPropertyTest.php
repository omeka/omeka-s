<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\ResourceClassProperty;

class ResourceClassPropertyTest extends \PHPUnit_Framework_TestCase
{
    protected $resourceClassProperty;

    public function setUp()
    {
        $this->resourceClassProperty = new ResourceClassProperty;
    }

    public function testInitialState()
    {
        $this->assertNull($this->resourceClassProperty->getId());
        $this->assertNull($this->resourceClassProperty->getAssigner());
        $this->assertNull($this->resourceClassProperty->getResourceClass());
        $this->assertNull($this->resourceClassProperty->getProperty());
        $this->assertNull($this->resourceClassProperty->getAlternateLabel());
        $this->assertNull($this->resourceClassProperty->getAlternateComment());
    }

    public function testSetState()
    {
        $this->resourceClassProperty->setAssigner('assigner');
        $this->resourceClassProperty->setResourceClass('resource_class');
        $this->resourceClassProperty->setProperty('property');
        $this->resourceClassProperty->setAlternateLabel('alternate_label');
        $this->resourceClassProperty->setAlternateComment('alternate_comment');
        $this->assertEquals('assigner', $this->resourceClassProperty->getAssigner());
        $this->assertEquals('resource_class', $this->resourceClassProperty->getResourceClass());
        $this->assertEquals('property', $this->resourceClassProperty->getProperty());
        $this->assertEquals('alternate_label', $this->resourceClassProperty->getAlternateLabel());
        $this->assertEquals('alternate_comment', $this->resourceClassProperty->getAlternateComment());
    }
}
