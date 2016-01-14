<?php
namespace OmekaTest\Model;

use Omeka\Entity\Property;
use Omeka\Entity\Value;
use Omeka\Test\TestCase;

class ValueTest extends TestCase
{
    protected $value;

    public function setUp()
    {
        $this->value = new Value;
    }

    public function testInitialState()
    {
        $this->assertNull($this->value->getId());
        $this->assertNull($this->value->getResource());
        $this->assertNull($this->value->getValueResource());
        $this->assertNull($this->value->getProperty());
        $this->assertNull($this->value->getType());
        $this->assertNull($this->value->getValue());
        $this->assertNull($this->value->getLang());
    }

    public function testSetResource()
    {
        $resource = $this->getMockForAbstractClass('Omeka\Entity\Resource');
        $this->value->setResource($resource);
        $this->assertSame($resource, $this->value->getResource());
    }

    public function testSetValueResource()
    {
        $valueResource = $this->getMockForAbstractClass('Omeka\Entity\Resource');
        $this->value->setValueResource($valueResource);
        $this->assertSame($valueResource, $this->value->getValueResource());
    }

    public function testSetProperty()
    {
        $property = new Property;
        $this->value->setProperty($property);
        $this->assertSame($property, $this->value->getProperty());
    }

    public function testSetValue()
    {
        $value = 'test-value';
        $this->value->setValue($value);
        $this->assertEquals($value, $this->value->getValue());
    }

    public function testSetlang()
    {
        $lang = 'test-lang';
        $this->value->setLang($lang);
        $this->assertEquals($lang, $this->value->getLang());
    }
}
