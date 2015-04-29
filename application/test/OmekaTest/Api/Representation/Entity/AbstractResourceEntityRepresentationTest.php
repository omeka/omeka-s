<?php
namespace OmekaTest\Api\Representation\Entity;

use Omeka\Test\TestCase;

class AbstractResourceEntityRepresentationTest extends TestCase
{
    public function testGetResourceClass()
    {
        $resourceId = 'test-resource_id';

        $resourceClass = $this->getMock('Omeka\Entity\ResourceClass');

        $resource = $this->getMock('Omeka\Entity\Resource');
        $resource->expects($this->once())
            ->method('getResourceClass')
            ->will($this->returnValue($resourceClass));

        $resourceClassAdapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $resourceClassAdapter->expects($this->once())
            ->method('getRepresentation')
            ->with(
                $this->equalTo(null),
                $this->equalTo($resourceClass)
            );

        $apiAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $apiAdapterManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($resourceClassAdapter));

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
        ));

        $adapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($resourceId, $resource, $adapter)
        );
        $this->assertNull($abstractResourceEntityRep->resourceClass());
    }

    public function testGetCreated()
    {
        $resourceId = 'test-resource_id';
        $resourceCreated = 'test-resource_created';

        $resource = $this->getMock('Omeka\Entity\Resource');
        $resource->expects($this->once())
            ->method('getCreated')
            ->will($this->returnValue($resourceCreated));

        $serviceLocator = $this->getServiceManager();

        $adapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($resourceId, $resource, $adapter)
        );
        $this->assertEquals($resourceCreated, $abstractResourceEntityRep->created());
    }

    public function testGetModified()
    {
        $resourceId = 'test-resource_id';
        $resourceModified = 'test-resource_modified';

        $resource = $this->getMock('Omeka\Entity\Resource');
        $resource->expects($this->once())
            ->method('getModified')
            ->will($this->returnValue($resourceModified));

        $serviceLocator = $this->getServiceManager();

        $adapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($resourceId, $resource, $adapter)
        );
        $this->assertEquals($resourceModified, $abstractResourceEntityRep->modified());
    }
}
