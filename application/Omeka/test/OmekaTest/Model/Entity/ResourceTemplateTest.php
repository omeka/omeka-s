<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\ResourceTemplateProperty;
use Omeka\Model\Entity\ResourceTemplate;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Model\Entity\User;
use Omeka\Test\TestCase;

class ResourceTemplateTest extends TestCase
{
    protected $resourceTemplate;

    public function setUp()
    {
        $this->resourceTemplate = new ResourceTemplate;
    }

    public function testInitialState()
    {
        $this->assertNull($this->resourceTemplate->getId());
        $this->assertNull($this->resourceTemplate->getLabel());
        $this->assertNull($this->resourceTemplate->getOwner());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->resourceTemplate->getResourceTemplateProperties()
        );
    }

    public function testSetLabel()
    {
        $label = 'test-label';
        $this->resourceTemplate->setLabel($label);
        $this->assertEquals($label, $this->resourceTemplate->getLabel());
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->resourceTemplate->setOwner($owner);
        $this->assertSame($owner, $this->resourceTemplate->getOwner());
        $this->assertTrue($owner->getResourceTemplates()->contains($this->resourceTemplate));
    }

    public function testAddResourceTemplateProperty()
    {
        $resourceTemplateProperty = new ResourceTemplateProperty;
        $this->resourceTemplate->addResourceTemplateProperty($resourceTemplateProperty);
        $this->assertSame($this->resourceTemplate, $resourceTemplateProperty->getResourceTemplate());
        $this->assertTrue($this->resourceTemplate->getResourceTemplateProperties()->contains($resourceTemplateProperty));
    }

    public function testRemoveResourceTemplateProperty()
    {
        $resourceTemplateProperty = new ResourceTemplateProperty;
        $this->resourceTemplate->addResourceTemplateProperty($resourceTemplateProperty);
        $this->resourceTemplate->removeResourceTemplateProperty($resourceTemplateProperty);
        $this->assertFalse($this->resourceTemplate->getResourceTemplateProperties()->contains($resourceTemplateProperty));
        $this->assertNull($resourceTemplateProperty->getResourceTemplate());
    }
}
