<?php
namespace OmekaTest\Api\Adapter\Entity;

use Omeka\Api\Adapter\ValueHydrator;
use Omeka\Entity\Property;
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
                array(), '', true, true, true,
                array('getEntityManager', 'getAdapter')
        );

        $collection = $this->getMock('Doctrine\Common\Collections\ArrayCollection');
        $collection->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(array()));

        $this->resource = $this->getMock('Omeka\Entity\Resource');
        $this->resource->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue($collection));
    }

    public function testHydrateLiteral()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    '@value' => 'test-@value',
                    '@language' => 'test-@language',
                    'property_id' => 'test-property_id',
                ),
            ),
        );

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
        $nodeObject = array(
            'term' => array(
                array(
                    'property_id' => 'test-property_id',
                    'value_resource_id' => 'test-value_resource_id',
                ),
            ),
        );

        $valueResource = $this->getMock('Omeka\Entity\Resource');
        $property = $this->getMock('Omeka\Entity\Property');
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Entity\Property'),
                $this->equalTo($nodeObject['term'][0]['property_id'])
            )
            ->will($this->returnValue($property));
        $entityManager->expects($this->once())
            ->method('find')
            ->with(
                $this->equalTo('Omeka\Entity\Resource'),
                $this->equalTo($nodeObject['term'][0]['value_resource_id'])
            )
            ->will($this->returnValue($valueResource));
        $this->adapter->expects($this->exactly(2))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydrateUri()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    '@id' => 'test-@id',
                    'property_id' => 'test-property_id',
                ),
            ),
        );

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
