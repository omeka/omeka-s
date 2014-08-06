<?php
namespace OmekaTest\Api\Representation;

use Omeka\Api\Representation\AbstractResourceRepresentation;
use Omeka\Test\TestCase;
use ReflectionClass;

class AbstractResourceRepresentationTest extends TestCase
{
    public function testConstructor()
    {
        $id = 'test_id';
        $data = 'test_data';
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager()));

        $abstractResourceRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractResourceRepresentation',
            array($id, $data, $adapter)
        );
        $abstractResourceRep->expects($this->once())
            ->method('getJsonLd')
            ->will($this->returnValue(array('foo' => 'bar')));

        // test getId()
        $this->assertEquals($id,  $abstractResourceRep->getId());

        // test jsonSerialize()
        $this->assertEquals(array(
            '@context' => array(
                AbstractResourceRepresentation::OMEKA_VOCABULARY_TERM
                => AbstractResourceRepresentation::OMEKA_VOCABULARY_IRI
            ),
            'foo' => 'bar',
        ), $abstractResourceRep->jsonSerialize());
    }

    public function testMethodsAreProtected()
    {
        $class = new ReflectionClass(
            'Omeka\Api\Representation\AbstractResourceRepresentation'
        );
        $protectedMethods = array(
            'addTermDefinitionToContext', 'setId', 'setAdapter', 'getAdapter',
        );
        foreach ($protectedMethods as $protectedMethod) {
            $this->assertTrue($class->getMethod($protectedMethod)->isProtected());
        }
    }
}
