<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\ItemSet;

class ItemSetTest extends \PHPUnit_Framework_TestCase
{
    protected $itemSet;

    public function setUp()
    {
        $this->itemSet = new ItemSet;
    }

    public function testInitialState()
    {
        $this->assertNull($this->itemSet->getId());
        $this->assertNull($this->itemSet->getOwner());
        $this->assertNull($this->itemSet->getResourceClass());
        $this->assertNull($this->itemSet->getSites());
    }

    public function testSetState()
    {
        $this->itemSet->setOwner('owner');
        $this->itemSet->setResourceClass('resource_class');
        $this->assertEquals('owner', $this->itemSet->getOwner());
        $this->assertEquals('resource_class', $this->itemSet->getResourceClass());
    }
}
