<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Item;
use Omeka\Model\Entity\ItemSet;
use Omeka\Model\Entity\Media;
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
        $this->assertFalse($this->item->isPublic());
        $this->assertFalse($this->item->isShareable());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getItemSets()
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getSiteItems()
        );
    }

    public function testSetIsPublic()
    {
        $this->item->setIsPublic(true);
        $this->assertTrue($this->item->isPublic());
    }

    public function testSetIsShareable()
    {
        $this->item->setIsShareable(true);
        $this->assertTrue($this->item->isShareable());
    }

    public function testAddMedia()
    {
        $media = new Media;
        $this->item->addMedia($media);
        $this->assertSame($this->item, $media->getItem());
        $this->assertTrue($this->item->getMedia()->contains($media));
    }

    public function testRemoveMedia()
    {
        $media = new Media;
        $this->item->addMedia($media);
        $this->item->removeMedia($media);
        $this->assertFalse($this->item->getMedia()->contains($media));
        $this->assertNull($media->getItem());
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
