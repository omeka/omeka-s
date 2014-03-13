<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\SiteResource;

class SiteResourceTest extends \PHPUnit_Framework_TestCase
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

    public function testSetState()
    {
        $this->siteResource->setAssigner('assigner');
        $this->siteResource->setSite('site');
        $this->siteResource->setResource('resource');
        $this->assertEquals('assigner', $this->siteResource->getAssigner());
        $this->assertEquals('site', $this->siteResource->getSite());
        $this->assertEquals('resource', $this->siteResource->getResource());
    }
}
