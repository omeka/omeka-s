<?php
namespace OmekaTest\Api\Representation\Entity;

use Omeka\Test\TestCase;

class AbstractResourceEntityRepresentationTest extends TestCase
{
    public function testGetResourceClass()
    {
        $resourceClass = $this->getMock('Omeka\Entity\ResourceClass');

        $resource = $this->getMock('Omeka\Entity\Resource');
        $resource->expects($this->once())
            ->method('getResourceClass')
            ->will($this->returnValue($resourceClass));

        $resourceClassAdapter = $this->getMock('Omeka\Api\Adapter\AbstractEntityAdapter');
        $resourceClassAdapter->expects($this->once())
            ->method('getRepresentation')
            ->with(
                $this->equalTo($resourceClass)
            );

        $apiAdapterManager = $this->getMockBuilder('Omeka\Api\Adapter\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $apiAdapterManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($resourceClassAdapter));

        $serviceLocator = $this->getServiceManager([
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
            'EventManager' => $this->getMock('Zend\EventManager\EventManager'),
        ]);

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractResourceEntityRepresentation',
            [$resource, $adapter]
        );
        $this->assertNull($abstractResourceEntityRep->resourceClass());
    }

    public function testGetCreated()
    {
        $resourceCreated = 'test-resource_created';

        $resource = $this->getMock('Omeka\Entity\Resource');
        $resource->expects($this->once())
            ->method('getCreated')
            ->will($this->returnValue($resourceCreated));

        $serviceLocator = $this->getServiceManager([
            'EventManager' => $this->getMock('Zend\EventManager\EventManager'),
        ]);

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractResourceEntityRepresentation',
            [$resource, $adapter]
        );
        $this->assertEquals($resourceCreated, $abstractResourceEntityRep->created());
    }

    public function testGetModified()
    {
        $resourceModified = 'test-resource_modified';

        $resource = $this->getMock('Omeka\Entity\Resource');
        $resource->expects($this->once())
            ->method('getModified')
            ->will($this->returnValue($resourceModified));

        $serviceLocator = $this->getServiceManager([
            'EventManager' => $this->getMock('Zend\EventManager\EventManager'),
        ]);

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractResourceEntityRepresentation',
            [$resource, $adapter]
        );
        $this->assertEquals($resourceModified, $abstractResourceEntityRep->modified());
    }
}
