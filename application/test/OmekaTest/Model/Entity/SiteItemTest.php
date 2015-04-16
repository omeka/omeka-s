<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Item;
use Omeka\Model\Entity\Site;
use Omeka\Model\Entity\SiteItem;
use Omeka\Model\Entity\User;
use Omeka\Test\TestCase;

class SiteItemTest extends TestCase
{
    protected $siteItem;

    public function setUp()
    {
        $this->siteItem = new SiteItem;
    }

    public function testInitialState()
    {
        $this->assertNull($this->siteItem->getId());
        $this->assertNull($this->siteItem->getAssigner());
        $this->assertNull($this->siteItem->getSite());
        $this->assertNull($this->siteItem->getItem());
    }

    public function testSetAssigner()
    {
        $assigner = new User;
        $this->siteItem->setAssigner($assigner);
        $this->assertSame($assigner, $this->siteItem->getAssigner());
    }

    public function testSetSite()
    {
        $site = new Site;
        $this->siteItem->setSite($site);
        $this->assertSame($site, $this->siteItem->getSite());
    }

    public function testSetItem()
    {
        $item = new Item;
        $this->siteItem->setItem($item);
        $this->assertSame($item, $this->siteItem->getItem());
    }
}
