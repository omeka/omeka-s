<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Value;

class ValueTest extends \PHPUnit_Framework_TestCase
{
    protected $value;

    public function setUp()
    {
        $this->value = new Value;
    }

    public function testInitialState()
    {
        $this->assertNull($this->value->getId());
        $this->assertNull($this->value->getOwner());
        $this->assertNull($this->value->getResource());
        $this->assertNull($this->value->getProperty());
        $this->assertNull($this->value->getType());
        $this->assertNull($this->value->getValue());
        $this->assertNull($this->value->getValueTransformed());
        $this->assertNull($this->value->getLang());
        $this->assertNull($this->value->getIsHtml());
        $this->assertNull($this->value->getValueResource());
    }

    public function testSetState()
    {
        $this->value->setOwner('owner');
        $this->value->setResource('resource');
        $this->value->setProperty('property');
        $this->value->setType('type');
        $this->value->setValue('value');
        $this->value->setValueTransformed('value_transformed');
        $this->value->setLang('lang');
        $this->value->setIsHtml('is_html');
        $this->value->setValueResource('value_resource');
        $this->assertEquals('owner', $this->value->getOwner());
        $this->assertEquals('resource', $this->value->getResource());
        $this->assertEquals('property', $this->value->getProperty());
        $this->assertEquals('type', $this->value->getType());
        $this->assertEquals('value', $this->value->getValue());
        $this->assertEquals('value_transformed', $this->value->getValueTransformed());
        $this->assertEquals('lang', $this->value->getLang());
        $this->assertEquals('is_html', $this->value->getIsHtml());
        $this->assertEquals('value_resource', $this->value->getValueResource());
    }
}
