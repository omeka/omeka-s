<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Item;
use Omeka\Model\Entity\ItemSet;
use Omeka\Test\TestCase;

class ItemSetTest extends TestCase
{
    protected $itemSet;

    public function setUp()
    {
        $this->itemSet = new ItemSet;
    }

    public function testInitialState()
    {
        $this->assertNull($this->itemSet->getId());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->itemSet->getItems()
        );
    }

    public function testAddItem()
    {
        $item= new Item;
        $this->itemSet->addItem($item);
        $this->assertTrue($this->itemSet->getItems()->contains($item));
    }

    public function testRemoveItem()
    {
        $item = new Item;
        $this->itemSet->addItem($item);
        $this->assertTrue($this->itemSet->removeItem($item));
    }
}
