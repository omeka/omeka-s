<?php
namespace OmekaTest\Api\Representation;

use Omeka\Test\TestCase;

class AbstractResourceRepresentationTest extends TestCase
{
    public function testConstructor()
    {
        $id = 'foo';
        $data = 'bar';
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');

        $mockRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractResourceRepresentation',
            array(), '', false, true, true,
            array('setServiceLocator', 'setId', 'setData', 'setAdapter')
        );

        $mockServiceManager = $this->getServiceManager();
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));
        $mockRep->expects($this->once())
            ->method('setServiceLocator')
            ->with($this->equalTo($mockServiceManager));
        $mockRep->expects($this->once())
            ->method('setId')
            ->with($this->equalTo($id));
        $mockRep->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($data));
        $mockRep->expects($this->once())
            ->method('setAdapter')
            ->with($this->equalTo($mockAdapter));

        $mockRep->__construct($id, $data, $mockAdapter);
    }

    public function testGetId()
    {
        $id = 'foo';
        $data = 'bar';
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');

        $mockServiceManager = $this->getServiceManager();
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));

        $mockRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractResourceRepresentation',
            array($id, $data, $mockAdapter), '', true, true, true, array()
        );

        $this->assertEquals($id, $mockRep->getId());
    }

    public function testGetAdapter()
    {
        $id = 'foo';
        $data = 'bar';
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');

        $mockServiceManager = $this->getServiceManager();
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));

        $mockRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractResourceRepresentation',
            array($id, $data, $mockAdapter), '', true, true, true, array()
        );

        $this->assertEquals($mockAdapter, $mockRep->getAdapter());
    }
}
