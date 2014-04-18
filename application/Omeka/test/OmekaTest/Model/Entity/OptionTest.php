<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Option;
use Omeka\Test\TestCase;

class OptionTest extends TestCase
{
    protected $module;

    public function setUp()
    {
        $this->module = new Option;
    }

    public function testInitialState()
    {
        $this->assertNull($this->module->getId());
        $this->assertNull($this->module->getValue());
    }

    public function testSetState()
    {
        $this->module->setId('test_id');
        $this->module->setValue('test_value');
        $this->assertEquals('test_id', $this->module->getId());
        $this->assertEquals('test_value', $this->module->getValue());
    }
}
