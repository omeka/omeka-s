<?php
namespace OmekaTest\Model;

use Omeka\Entity\SitePermission;
use Omeka\Test\TestCase;

class SitePermissionTest extends TestCase
{
    protected $sitePermission;

    public function setUp()
    {
        $this->sitePermission = new SitePermission;
    }

    public function testInitialState()
    {
        $this->assertNull($this->sitePermission->getId());
        $this->assertNull($this->sitePermission->getSite());
        $this->assertNull($this->sitePermission->getUser());
        $this->assertNull($this->sitePermission->getRole());
    }
}
