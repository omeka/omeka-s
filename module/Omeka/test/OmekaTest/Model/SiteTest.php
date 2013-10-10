<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Site;

class SiteTest extends \PHPUnit_Framework_TestCase
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
