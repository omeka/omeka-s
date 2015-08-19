<?php
namespace OmekaTest\Model;

use Omeka\Entity\Site;
use Omeka\Entity\User;
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
        $this->assertNull($this->site->getOwner());
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->site->setOwner($owner);
        $this->assertSame($owner, $this->site->getOwner());
    }
}
