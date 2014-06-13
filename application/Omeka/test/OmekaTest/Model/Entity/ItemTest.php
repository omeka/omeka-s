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
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getItemSets()
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getSites()
        );
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
