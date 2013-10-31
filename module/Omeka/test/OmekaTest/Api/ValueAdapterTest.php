<?php
namespace OmekaTest\Api;

use Omeka\Api\Adapter\Entity\ValueAdapter;
use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\Item;
use Omeka\Model\Entity\Media;
use Omeka\Model\Entity\User;
use Omeka\Model\Entity\Value;
use Omeka\Test\MockBuilder;

class ValueAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $data = array(
        'owner' => array('id' => 1),
        'resource' => array('id' => 2),
        'property' => array('id' => 3),
        'type' => 'Type',
        'value' => 'Value',
        'value_transformed' => 'ValueTransformed',
        'lang' => 'Lang',
        'is_html' => true,
        'value_resource' => array('id' => 4),
    );

    public function setUp()
    {
        $this->adapter = new ValueAdapter;
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(
            'Omeka\Model\Entity\Value',
            $this->adapter->getEntityClass()
        );
    }

    public function testHydrate()
    {
        $builder = new MockBuilder;
        $entityRepository = $builder->getEntityRepository();
        $entityManager = $builder->getEntityManager();
        $serviceManager = $builder->getServiceManager('EntityManager', $entityManager);

        $entityManager->expects($this->exactly(4))
            ->method('getRepository')
            ->with($this->logicalOr(
                $this->equalTo('Omeka\Model\Entity\User'),
                $this->equalTo('Omeka\Model\Entity\Resource'),
                $this->equalTo('Omeka\Model\Entity\Property')
            ))
            ->will($this->returnValue($entityRepository));
        $entityRepository->expects($this->exactly(4))
            ->method('find')
            ->with($this->logicalOr(
                $this->equalTo($this->data['owner']['id']),
                $this->equalTo($this->data['resource']['id']),
                $this->equalTo($this->data['property']['id']),
                $this->equalTo($this->data['value_resource']['id'])
            ))
            ->will($this->onConsecutiveCalls(
                new User,
                $this->getMockForAbstractClass('Omeka\Model\Entity\Resource'),
                new Property,
                $this->getMockForAbstractClass('Omeka\Model\Entity\Resource'))
            );

        $this->adapter->setServiceLocator($serviceManager);
        $entity = new Value;
        $this->adapter->hydrate($this->data, $entity);

        $this->assertNull($entity->getOwner()->getId());
        $this->assertInstanceOf('Omeka\Model\Entity\User', $entity->getOwner());
        $this->assertNull($entity->getResource()->getId());
        $this->assertInstanceOf('Omeka\Model\Entity\Resource', $entity->getResource());
        $this->assertNull($entity->getProperty()->getId());
        $this->assertInstanceOf('Omeka\Model\Entity\Property', $entity->getProperty());
        $this->assertEquals($this->data['type'], $entity->getType());
        $this->assertEquals($this->data['value'], $entity->getValue());
        $this->assertEquals($this->data['value_transformed'], $entity->getValueTransformed());
        $this->assertEquals($this->data['lang'], $entity->getLang());
        $this->assertTrue($entity->getIsHtml());
        $this->assertNull($entity->getValueResource()->getId());
        $this->assertInstanceOf('Omeka\Model\Entity\Resource', $entity->getValueResource());
    }

    public function testExtract()
    {
        $entity = new Value;
        $entity->setOwner(new User);
        $entity->setResource(new Item);
        $entity->setProperty(new Property);
        $entity->setType($this->data['type']);
        $entity->setValue($this->data['value']);
        $entity->setValueTransformed($this->data['value_transformed']);
        $entity->setLang($this->data['lang']);
        $entity->setIsHtml($this->data['is_html']);
        $entity->setValueResource(new Item);
        $data = $this->adapter->extract($entity);
        $this->assertNull($data['id']);
        $this->assertInternalType('array', $data['owner']);
        $this->assertInternalType('array', $data['resource']);
        $this->assertInternalType('array', $data['property']);
        $this->assertEquals($this->data['type'], $data['type']);
        $this->assertEquals($this->data['value'], $data['value']);
        $this->assertEquals($this->data['value_transformed'], $data['value_transformed']);
        $this->assertEquals($this->data['lang'], $data['lang']);
        $this->assertEquals($this->data['is_html'], $data['is_html']);
        $this->assertInternalType('array', $data['value_resource']);
    }
}
