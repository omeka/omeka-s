<?php
namespace OmekaTest\Model;

use Omeka\Entity\Property;
use Omeka\Entity\ResourceTemplateProperty;
use Omeka\Entity\ResourceTemplate;
use Omeka\Test\TestCase;

class ResourceTemplatePropertyTest extends TestCase
{
    protected $resourceTemplateProperty;

    public function setUp()
    {
        $this->resourceTemplateProperty = new ResourceTemplateProperty;
    }

    public function testInitialState()
    {
        $this->assertNull($this->resourceTemplateProperty->getId());
        $this->assertNull($this->resourceTemplateProperty->getResourceTemplate());
        $this->assertNull($this->resourceTemplateProperty->getProperty());
        $this->assertNull($this->resourceTemplateProperty->getAlternateLabel());
        $this->assertNull($this->resourceTemplateProperty->getAlternateComment());
    }

    public function testSetResourceTemplate()
    {
        $resourceTemplate = new ResourceTemplate;
        $this->resourceTemplateProperty->setResourceTemplate($resourceTemplate);
        $this->assertSame($resourceTemplate, $this->resourceTemplateProperty->getResourceTemplate());
    }

    public function testSetProperty()
    {
        $property = new Property;
        $this->resourceTemplateProperty->setProperty($property);
        $this->assertSame($property, $this->resourceTemplateProperty->getProperty());
    }

    public function testSetAlternateLabel()
    {
        $alternateLabel = 'test-alternateLabel';
        $this->resourceTemplateProperty->setAlternateLabel($alternateLabel);
        $this->assertEquals($alternateLabel, $this->resourceTemplateProperty->getAlternateLabel());
    }

    public function testSetAlternateComment()
    {
        $alternateComment = 'test-alternateComment';
        $this->resourceTemplateProperty->setAlternateComment($alternateComment);
        $this->assertEquals($alternateComment, $this->resourceTemplateProperty->getAlternateComment());
    }
}
