<?php
namespace OmekaTest\Api\Representation;

use Omeka\Api\Representation\ResourceReference;
use Omeka\Test\TestCase;

class ResourceReferenceTest extends TestCase
{
    protected $id;
    protected $data;
    protected $adapter;
    protected $viewHelperManager;

    public function setUp()
    {
        $this->id = 'test_id';
        $this->resource = $this->getMockForAbstractClass('Omeka\Api\ResourceInterface');
        $this->resource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($this->id));

        $this->viewHelperManager = $this->createMock('Interop\Container\ContainerInterface');
        $this->adapter = $this->createMock('Omeka\Api\Adapter\AbstractAdapter');
        $this->adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($this->getServiceManager([
                'EventManager' => $this->createMock('Zend\EventManager\EventManager'),
                'ViewHelperManager' => $this->viewHelperManager,
            ])));
    }

    public function testJsonSerialize()
    {
        $jsonLdId = 'test_@id';

        $this->viewHelperManager->expects($this->once())
            ->method('get')
            ->with('Url')
            ->will($this->returnValue(function () use ($jsonLdId) {
                return $jsonLdId;
            }));

        $resourceReference = new ResourceReference($this->resource, $this->adapter);

        $this->assertEquals([
            '@id' => $jsonLdId,
            'o:id' => $this->id,
        ], $resourceReference->jsonSerialize());
    }
}
