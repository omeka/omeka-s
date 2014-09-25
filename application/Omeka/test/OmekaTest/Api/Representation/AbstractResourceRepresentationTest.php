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
        $url = 'test_url';
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager()));

        $abstractResourceRep = $this->getMock(
            'Omeka\Api\Representation\AbstractResourceRepresentation',
            array('getJsonLd', 'apiUrl'),
            array($id, $data, $adapter)
        );
        $abstractResourceRep->expects($this->once())
            ->method('getJsonLd')
            ->will($this->returnValue(array('foo' => 'bar')));
        $abstractResourceRep->expects($this->once())
            ->method('apiUrl')
            ->will($this->returnValue($url));

        // test getId()
        $this->assertEquals($id,  $abstractResourceRep->getId());

        // test jsonSerialize()
        $this->assertEquals(array(
            '@context' => array(
                AbstractResourceRepresentation::OMEKA_VOCABULARY_TERM
                => AbstractResourceRepresentation::OMEKA_VOCABULARY_IRI
            ),
            '@id' => $url,
            'o:id' => $id,
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
