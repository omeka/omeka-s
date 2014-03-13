<?php
namespace Omeka\Stdlib;

use Omeka\Stdlib\ClassCheck;

class ClassCheckTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->classCheck = new ClassCheck;
    }

    public function testIsInterfaceOf()
    {
        $this->assertTrue($this->classCheck->isInterfaceOf(
            'Omeka\Stdlib\TestInterface',
            'Omeka\Stdlib\ValidChildClass'
        ));
        $this->assertTrue($this->classCheck->isInterfaceOf(
            'Omeka\Stdlib\TestInterface',
            new ValidChildClass
        ));
        $this->assertFalse($this->classCheck->isInterfaceOf(
            'Omeka\Stdlib\TestInterface',
            'Omeka\Stdlib\InvalidChildClass'
        ));
        $this->assertFalse($this->classCheck->isInterfaceOf(
            'Omeka\Stdlib\TestInterface',
            new InvalidChildClass
        ));
    }
}

interface TestInterface
{}

class TestParentClass
{}

class ValidChildClass extends TestParentClass implements TestInterface
{}

class InvalidChildClass
{}
