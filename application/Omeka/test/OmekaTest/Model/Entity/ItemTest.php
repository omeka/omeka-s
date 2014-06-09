<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Item;
use Omeka\Model\Entity\ItemSet;
use Omeka\Test\TestCase;

class ItemTest extends TestCase
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
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getItemSets()
        );
    }

    public function testSetState()
    {
        $this->item->setOwner('owner');
        $this->item->setResourceClass('resource_class');
        $this->assertEquals('owner', $this->item->getOwner());
        $this->assertEquals('resource_class', $this->item->getResourceClass());
    }

    public function testAddToItemSet()
    {
        $itemSet = new ItemSet;
        $this->item->addToItemSet($itemSet);
        $this->assertTrue($this->item->getItemSets()->contains($itemSet));
    }

    public function testRemoveFromItemSet()
    {
        $itemSet = new ItemSet;
        $this->item->addToItemSet($itemSet);
        $this->assertTrue($this->item->removeFromItemSet($itemSet));
    }
}
