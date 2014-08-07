<?php
namespace OmekaTest\Api\Representation;

use Omeka\Api\Representation\ResourceReference;
use Omeka\Test\TestCase;

class ResourceReferenceTest extends TestCase
{
    protected $id;
    protected $data;
    protected $adapter;

    public function setUp()
    {
        $this->id = 'test_id';
        $this->data = 'test_data';
        $this->adapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');
        $this->adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager()));
    }

    public function testGetRepresentation()
    {
        $this->adapter->expects($this->once())
            ->method('getRepresentation')
            ->with($this->equalTo($this->id), $this->equalTo($this->data));

        $resourceReference = new ResourceReference(
            $this->id, $this->data, $this->adapter
        );
        $representation = $resourceReference->getRepresentation();
    }

    public function testJsonSerialize()
    {
        $jsonLdId = 'test_@id';
        $this->adapter->expects($this->once())
            ->method('getApiUrl')
            ->with($this->equalTo($this->data))
            ->will($this->returnValue($jsonLdId));

        $resourceReference = new ResourceReference(
            $this->id, $this->data, $this->adapter
        );
        $this->assertEquals(array(
            '@id' => $jsonLdId,
            'o:id' => $this->id,
        ), $resourceReference->jsonSerialize());
    }

    public function testGetJsonLd()
    {
        $resourceReference = new ResourceReference(
            $this->id, $this->data, $this->adapter
        );
        $this->assertNull($resourceReference->getJsonLd());
    }
}
