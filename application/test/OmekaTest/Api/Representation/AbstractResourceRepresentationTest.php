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

        $resource = $this->getMockForAbstractClass('Omeka\Api\ResourceInterface');
        $resource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager()));

        $abstractResourceRep = $this->getMock(
            'Omeka\Api\Representation\AbstractResourceRepresentation',
            ['getJsonLd', 'apiUrl', 'getJsonLdType'],
            [$resource, $adapter]
        );
        $abstractResourceRep->expects($this->once())
            ->method('getJsonLd')
            ->will($this->returnValue(['foo' => 'bar']));
        $abstractResourceRep->expects($this->once())
            ->method('apiUrl')
            ->will($this->returnValue($url));
        $abstractResourceRep->expects($this->once())
            ->method('getJsonLdType')
            ->will($this->returnValue('o:fooType'));

        // test getId()
        $this->assertEquals($id,  $abstractResourceRep->id());

        // test jsonSerialize()
        $this->assertEquals([
            '@context' => [
                AbstractResourceRepresentation::OMEKA_VOCABULARY_TERM
                => AbstractResourceRepresentation::OMEKA_VOCABULARY_IRI
            ],
            '@id' => $url,
            '@type' => 'o:fooType',
            'o:id' => $id,
            'foo' => 'bar',
        ], $abstractResourceRep->jsonSerialize());
    }

    public function testMethodsAreProtected()
    {
        $class = new ReflectionClass(
            'Omeka\Api\Representation\AbstractResourceRepresentation'
        );
        $protectedMethods = [
            'addTermDefinitionToContext', 'setId', 'setAdapter', 'getAdapter',
        ];
        foreach ($protectedMethods as $protectedMethod) {
            $this->assertTrue($class->getMethod($protectedMethod)->isProtected());
        }
    }
}
