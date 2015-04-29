<?php
namespace OmekaTest\Model;

use Omeka\Entity\Module;
use Omeka\Test\TestCase;

class ModuleTest extends TestCase
{
    protected $module;

    public function setUp()
    {
        $this->module = new Module;
    }

    public function testInitialState()
    {
        $this->assertNull($this->module->getId());
        $this->assertFalse($this->module->isActive());
        $this->assertNull($this->module->getVersion());
    }

    public function testSetIsActive()
    {
        $this->module->setIsActive(true);
        $this->assertTrue($this->module->isActive());
    }

    public function testSetVersion()
    {
        $version = 'test-version';
        $this->module->setVersion($version);
        $this->assertEquals($version, $this->module->getVersion());
    }
}
