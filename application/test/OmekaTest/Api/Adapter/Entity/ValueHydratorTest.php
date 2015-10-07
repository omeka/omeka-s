<?php
namespace OmekaTest\Api\Adapter\Entity;

use Omeka\Api\Adapter\ValueHydrator;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Omeka\Test\TestCase;

class ValueHydratorTest extends TestCase
{
    protected $adapter;
    protected $resource;

    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\AbstractEntityAdapter',
                [], '', true, true, true,
                ['getEntityManager', 'getAdapter']
        );

        $collection = $this->getMock('Doctrine\Common\Collections\ArrayCollection');
        $collection->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([]));

        $this->resource = $this->getMock('Omeka\Entity\Resource');
        $this->resource->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue($collection));
    }

    public function testHydrateLiteral()
    {
        $nodeObject = [
            'term' => [
                [
                    '@value' => 'test-@value',
                    '@language' => 'test-@language',
                    'property_id' => 'test-property_id',
                ],
            ],
        ];

        $property = $this->getMock('Omeka\Entity\Property');
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Entity\Property'),
                $this->equalTo($nodeObject['term'][0]['property_id'])
            )
            ->will($this->returnValue($property));
        $this->adapter->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydrateResource()
    {
        $nodeObject = [
            'term' => [
                [
                    'property_id' => 'test-property_id',
                    'value_resource_id' => 'test-value_resource_id',
                ],
            ],
        ];

        $valueResource = $this->getMock('Omeka\Entity\Resource');
        $property = $this->getMock('Omeka\Entity\Property');
        $entityManager = $this->getEntityManager();

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydrateUri()
    {
        $nodeObject = [
            'term' => [
                [
                    '@id' => 'test-@id',
                    'property_id' => 'test-property_id',
                ],
            ],
        ];

        $property = $this->getMock('Omeka\Entity\Property');
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Entity\Property'),
                $this->equalTo($nodeObject['term'][0]['property_id'])
            )
            ->will($this->returnValue($property));
        $this->adapter->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }
}
