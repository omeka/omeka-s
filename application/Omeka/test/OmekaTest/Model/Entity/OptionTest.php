<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Option;
use Omeka\Test\TestCase;

class OptionTest extends TestCase
{
    protected $option;

    public function setUp()
    {
        $this->option = new Option;
    }

    public function testInitialState()
    {
        $this->assertNull($this->option->getId());
        $this->assertNull($this->option->getValue());
    }

    public function testSetId()
    {
        $id = 'test-id';
        $this->option->setId($id);
        $this->assertEquals($id, $this->option->getId());
    }

    public function testSetValue()
    {
        $value = 'test-value';
        $this->option->setValue($value);
        $this->assertEquals($value, $this->option->getValue());
    }
}
