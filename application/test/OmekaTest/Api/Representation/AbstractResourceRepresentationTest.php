<?php
namespace OmekaTest\Api\Representation;

use Omeka\Test\TestCase;
use ReflectionClass;
use Zend\EventManager\EventManager;

class AbstractResourceRepresentationTest extends TestCase
{
    public function testConstructor()
    {
        $id = 'test_id';
        $data = 'test_data';
        $url = 'test_url';
        $context = 'test_context';

        $urlHelper = $this->createMock('Zend\View\Helper\Url');
        $urlHelper->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($context));

        $resource = $this->getMockForAbstractClass('Omeka\Api\ResourceInterface');
        $resource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $adapter = $this->createMock('Omeka\Api\Adapter\AdapterInterface');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager([
                'EventManager' => new EventManager,
            ])));

        $abstractResourceRep = $this->getMockBuilder('Omeka\Api\Representation\AbstractResourceRepresentation')
            ->setMethods(['getJsonLd', 'apiUrl', 'getJsonLdType', 'getViewHelper'])
            ->setConstructorArgs([$resource, $adapter])
            ->getMock();
        $abstractResourceRep->expects($this->once())
            ->method('getViewHelper')
            ->will($this->returnValue($urlHelper));
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
        $this->assertEquals($id, $abstractResourceRep->id());

        // test jsonSerialize()
        $this->assertEquals([
            '@context' => $context,
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
            'setId', 'setAdapter', 'getAdapter',
        ];
        foreach ($protectedMethods as $protectedMethod) {
            $this->assertTrue($class->getMethod($protectedMethod)->isProtected());
        }
    }
}
