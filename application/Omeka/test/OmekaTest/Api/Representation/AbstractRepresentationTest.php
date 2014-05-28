<?php
namespace OmekaTest\Api\Representation;

use Omeka\Test\TestCase;

class AbstractRepresentationTest extends TestCase
{
    protected $mockRep;

    public function setUp()
    {
        $this->mockRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractRepresentation',
            array(), '', true, true, true,
            array('validateData')
        );
    }

    public function testSetDataValidates()
    {
        $data = 'foo';

        $this->mockRep->expects($this->once())
            ->method('validateData')
            ->with($this->equalTo($data));

        $this->mockRep->setData($data);
    }

    public function testGetDataIsProtected()
    {
        $class = new \ReflectionClass(
            'Omeka\Api\Representation\AbstractRepresentation'
        );
        $method = $class->getMethod('getData');
        $this->assertTrue($method->isProtected());
    }

    public function testValidateDataReturnsNull()
    {
        $data = 'foo';
        $this->assertNull($this->mockRep->validateData($data));
    }

    public function testGetAdapter()
    {
        $resourceName = 'foo';
        $adapter = 'bar';

        $mockAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $mockAdapterManager->expects($this->once())
            ->method('get')
            ->with($this->equalTo($resourceName))
            ->will($this->returnValue($adapter));
        $mockServiceManager = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $mockAdapterManager,
        ));

        $this->mockRep->setServiceLocator($mockServiceManager);
        $this->assertEquals($adapter, $this->mockRep->getAdapter($resourceName));
    }

    public function testGetReference()
    {
        $id = 'foo';
        $data = 'bar';
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');

        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager()));

        $this->assertInstanceOf(
            'Omeka\Api\Representation\ResourceReference',
            $this->mockRep->getReference($id, $data, $mockAdapter)
        );
    }

    public function testGetReferenceNullData()
    {
        $id = 'foo';
        $data = null;
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');

        $this->assertNull($this->mockRep->getReference($id, $data, $mockAdapter));
    }

    public function testGetReferenceEntityData()
    {
        $entityId = 1234;
        $mockEntity = $this->getMock('Omeka\Model\Entity\EntityInterface');
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');

        $mockEntity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($entityId));
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager()));

        $this->assertInstanceOf(
            'Omeka\Api\Representation\ResourceReference',
            $this->mockRep->getReference(null, $mockEntity, $mockAdapter)
        );
    }

    public function testGetDateTimeRequiresDateTime()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->mockRep->getDateTime(new \stdClass);
    }

    public function testGetDateTimeReturnsStdlibDateTime()
    {
        $this->assertInstanceOf(
            'Omeka\Stdlib\DateTime',
            $this->mockRep->getDateTime(new \DateTime)
        );
    }
}
