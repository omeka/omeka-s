<?php
namespace OmekaTest\Model;

use Omeka\Entity\Migration;
use Omeka\Test\TestCase;

class MigrationTest extends TestCase
{
    protected $migration;

    public function setUp()
    {
        $this->migration = new Migration;
    }

    public function testInitialState()
    {
        $this->assertNull($this->migration->getId());
        $this->assertNull($this->migration->getVersion());
    }

    public function testSetVersion()
    {
        $version = 'test-version';
        $this->migration->setVersion($version);
        $this->assertEquals($version, $this->migration->getVersion());
    }
}
