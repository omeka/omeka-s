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
        $this->resource = $this->getMockForAbstractClass('Omeka\Api\ResourceInterface');
        $this->resource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($this->id));
        $this->adapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');
        $this->adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager([
                'EventManager' => $this->getMock('Zend\EventManager\EventManager')
            ])));
    }

    public function testGetRepresentation()
    {
        $this->adapter->expects($this->once())
            ->method('getRepresentation')
            ->with($this->equalTo($this->resource));

        $resourceReference = new ResourceReference(
            $this->resource, $this->adapter
        );
        $representation = $resourceReference->getRepresentation();
    }

    public function testJsonSerialize()
    {
        $jsonLdId = 'test_@id';

        $resourceReference = $this->getMock(
            'Omeka\Api\Representation\ResourceReference',
            ['apiUrl'],
            [$this->resource, $this->adapter]
        );
        $resourceReference->expects($this->once())
            ->method('apiUrl')
            ->will($this->returnValue($jsonLdId));

        $this->assertEquals([
            '@id' => $jsonLdId,
            'o:id' => $this->id,
        ], $resourceReference->jsonSerialize());
    }

    public function testGetJsonLd()
    {
        $resourceReference = new ResourceReference(
            $this->resource, $this->adapter
        );
        $this->assertNull($resourceReference->getJsonLd());
    }
}
