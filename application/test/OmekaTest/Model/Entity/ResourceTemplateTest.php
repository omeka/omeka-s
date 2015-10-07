<?php
namespace OmekaTest\Model;

use Omeka\Entity\ResourceTemplate;
use Omeka\Entity\User;
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
    }
}
