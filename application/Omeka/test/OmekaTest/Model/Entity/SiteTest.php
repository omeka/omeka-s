<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Site;
use Omeka\Test\TestCase;

class SiteTest extends TestCase
{
    protected $site;

    public function setUp()
    {
        $this->site = new Site;
    }

    public function testInitialState()
    {
        $this->assertNull($this->site->getId());
    }

    public function testSetState()
    {
    }
}
