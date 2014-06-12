<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Site;
use Omeka\Model\Entity\SiteResource;
use Omeka\Model\Entity\User;
use Omeka\Test\TestCase;

class SiteResourceTest extends TestCase
{
    protected $siteResource;

    public function setUp()
    {
        $this->siteResource = new SiteResource;
    }

    public function testInitialState()
    {
        $this->assertNull($this->siteResource->getId());
        $this->assertNull($this->siteResource->getAssigner());
        $this->assertNull($this->siteResource->getSite());
        $this->assertNull($this->siteResource->getResource());
    }

    public function testSetAssigner()
    {
        $assigner = new User;
        $this->siteResource->setAssigner($assigner);
        $this->assertSame($assigner, $this->siteResource->getAssigner());
    }

    public function testSetSite()
    {
        $site = new Site;
        $this->siteResource->setSite($site);
        $this->assertSame($site, $this->siteResource->getSite());
    }

    public function testSetResource()
    {
        $resource = $this->getMockForAbstractClass('Omeka\Model\Entity\Resource');
        $this->siteResource->setResource($resource);
        $this->assertSame($resource, $this->siteResource->getResource());
    }
}
