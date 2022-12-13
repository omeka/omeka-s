<?php
namespace OmekaTest\Entity;

use Omeka\Entity\ItemSet;
use Omeka\Test\TestCase;

class ItemSetTest extends TestCase
{
    protected $itemSet;

    public function setUp(): void
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
}
