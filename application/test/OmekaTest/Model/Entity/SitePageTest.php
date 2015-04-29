<?php
namespace OmekaTest\Model;

use Omeka\Entity\Site;
use Omeka\Entity\SitePage;
use Omeka\Test\TestCase;

class SitePageTest extends TestCase
{
    protected $page;

    public function setUp()
    {
        $this->page = new SitePage;
    }

    public function testInitialState()
    {
        $this->assertNull($this->page->getId());
        $this->assertNull($this->page->getSite());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->page->getBlocks()
        );
    }

    public function testSetSite()
    {
        $site = new Site;
        $this->page->setSite($site);
        $this->assertSame($site, $this->page->getSite());
    }
}
