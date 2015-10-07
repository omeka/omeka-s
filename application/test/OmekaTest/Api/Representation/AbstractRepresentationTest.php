<?php
namespace OmekaTest\Api\Representation;

use Omeka\Test\TestCase;
use ReflectionClass;

class AbstractRepresentationTest extends TestCase
{
    public function testSetServiceLocator()
    {
        $serviceLocator = $this->getServiceManager();
        $abstractRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractRepresentation'
        );
        $abstractRep->setServiceLocator($serviceLocator);
        $this->assertSame($serviceLocator, $abstractRep->getServiceLocator());
    }

    public function testMethodsAreProtected()
    {
        $class = new ReflectionClass(
            'Omeka\Api\Representation\AbstractRepresentation'
        );
        $protectedMethods = [
            'getData', 'setData', 'validateData', 'getAdapter', 'getDateTime',
            'getTranslator',
        ];
        foreach ($protectedMethods as $protectedMethod) {
            $this->assertTrue($class->getMethod($protectedMethod)->isProtected());
        }
    }
}
