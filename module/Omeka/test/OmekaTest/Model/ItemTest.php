<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Item;

class ItemTest extends \PHPUnit_Framework_TestCase
{
    protected $item;

    public function setUp()
    {
        $this->item = new Item;
    }

    public function testInitialState()
    {
        $this->assertNull($this->item->getId());
        $this->assertNull($this->item->getOwner());
        $this->assertNull($this->item->getResourceClass());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getSites()
        );
    }

    public function testSetState()
    {
        $this->item->setOwner('owner');
        $this->item->setResourceClass('resource_class');
        $this->assertEquals('owner', $this->item->getOwner());
        $this->assertEquals('resource_class', $this->item->getResourceClass());
    }
}
