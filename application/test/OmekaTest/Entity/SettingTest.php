<?php
namespace OmekaTest\Entity;

use Omeka\Entity\Setting;
use Omeka\Test\TestCase;

class SettingTest extends TestCase
{
    protected $setting;

    public function setUp(): void
    {
        $this->setting = new Setting;
    }

    public function testInitialState()
    {
        $this->assertNull($this->setting->getId());
        $this->assertNull($this->setting->getValue());
    }

    public function testSetId()
    {
        $id = 'test-id';
        $this->setting->setId($id);
        $this->assertEquals($id, $this->setting->getId());
    }

    public function testSetValue()
    {
        $value = 'test-value';
        $this->setting->setValue($value);
        $this->assertEquals($value, $this->setting->getValue());
    }
}
