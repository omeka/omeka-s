<?php
namespace OmekaTest\Api\Representation;

use Omeka\Api\Representation\ResourceReference;
use Omeka\Test\TestCase;

class ResourceReferenceTest extends TestCase
{
    public function testGetRepresentation()
    {
        $id = 'test-id';
        $data = 'test-data';
        $mockAdapter = $this->getMock(
            'Omeka\Api\Adapter\AbstractAdapter'
        );

        $mockServiceManager = $this->getServiceManager();
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));
        $mockAdapter->expects($this->once())
            ->method('getRepresentation')
            ->with($this->equalTo($id), $this->equalTo($data));

        $reference = new ResourceReference($id, $data, $mockAdapter);
        $reference->getRepresentation();
    }

    public function testJsonSerialize()
    {
        $id = 'test-id';
        $data = 'test-data';
        $mockAdapter = $this->getMock(
            'Omeka\Api\Adapter\AbstractAdapter'
        );

        $mockServiceManager = $this->getServiceManager();
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));
        $mockAdapter->expects($this->once())
            ->method('getApiUrl')
            ->with($this->equalTo($data));

        $reference = new ResourceReference($id, $data, $mockAdapter);
        $this->assertEquals(
            array('@id' => null, 'id' => $id),
            $reference->jsonSerialize()
        );
    }
}
