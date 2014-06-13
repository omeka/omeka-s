<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\Value;
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
        $this->assertNull($this->value->getValueTransformed());
        $this->assertNull($this->value->getLang());
        $this->assertFalse($this->value->isHtml());
    }

    public function testSetResource()
    {
        $resource = $this->getMockForAbstractClass('Omeka\Model\Entity\Resource');
        $this->value->setResource($resource);
        $this->assertSame($resource, $this->value->getResource());
        $this->assertTrue($resource->getValues()->contains($this->value));
    }

    public function testSetValueResource()
    {
        $valueResource = $this->getMockForAbstractClass('Omeka\Model\Entity\Resource');
        $this->value->setValueResource($valueResource);
        $this->assertSame($valueResource, $this->value->getValueResource());
    }

    public function testSetProperty()
    {
        $property = new Property;
        $this->value->setProperty($property);
        $this->assertSame($property, $this->value->getProperty());
    }

    public function testSetType()
    {
        foreach ($this->value->getValidTypes() as $type) {
            $this->value->setType($type);
            $this->assertEquals($type, $this->value->getType());
        }
    }

    public function testSetValue()
    {
        $value = 'test-value';
        $this->value->setValue($value);
        $this->assertEquals($value, $this->value->getValue());
    }

    public function testSetValueTransformed()
    {
        $valueTransformed = 'test-valueTransformed';
        $this->value->setValueTransformed($valueTransformed);
        $this->assertEquals($valueTransformed, $this->value->getValueTransformed());
    }

    public function testSetlang()
    {
        $lang = 'test-lang';
        $this->value->setLang($lang);
        $this->assertEquals($lang, $this->value->getLang());
    }

    public function testIsHtml()
    {
        $this->value->setIsHtml(true);
        $this->asserttrue($this->value->isHtml());
    }

}
