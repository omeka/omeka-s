<?php
namespace OmekaTest\Entity;

use Omeka\Entity\Item;
use Omeka\Test\TestCase;

class ItemTest extends TestCase
{
    protected $item;

    public function setUp(): void
    {
        $this->item = new Item;
    }

    public function testInitialState()
    {
        $this->assertNull($this->item->getId());
        $this->assertTrue($this->item->isPublic());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getItemSets()
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->item->getSiteBlockAttachments()
        );
    }

    public function testSetIsPublic()
    {
        $this->item->setIsPublic(true);
        $this->assertTrue($this->item->isPublic());
    }
}
