<?php
namespace OmekaTest\Api\Adapter\Entity;

use Omeka\Api\Adapter\Entity\ValueHydrator;
use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\Resource;
use Omeka\Model\Entity\Value;
use Omeka\Test\TestCase;

class ValueHydratorTest extends TestCase
{
    protected $adapter;
    protected $resource;

    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\Entity\AbstractEntityAdapter',
                array(), '', true, true, true,
                array('getEntityManager', 'getAdapter', 'getApiUrl')
        );
        $this->resource = $this->getMock('Omeka\Model\Entity\Resource');
        $this->resource->expects($this->any())
            ->method('getValues')
            ->will($this->returnValue(
                $this->getMock('Doctrine\Common\Collections\ArrayCollection')
            ));
        }

    public function testHydrateRemoves()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    'value_id' => 'test-value_id',
                    'delete' => true,
                ),
            ),
        );

        $value = $this->getMock('Omeka\Model\Entity\Value');
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Value'),
                $this->equalTo($nodeObject['term'][0]['value_id'])
            )
            ->will($this->returnValue($value));
        $entityManager->expects($this->once())
            ->method('remove')
            ->with($this->isInstanceOf('Omeka\Model\Entity\Value'));
        $this->adapter->expects($this->exactly(2))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydrateModifiesLiteral()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    '@value' => 'test-@value',
                    '@language' => 'test-@language',
                    'value_id' => 'test-value_id',
                    'is_html' => true,
                ),
            ),
        );

        $value = $this->getMock('Omeka\Model\Entity\Value');
        $value->expects($this->once())
            ->method('setType')
            ->with($this->equalTo(Value::TYPE_LITERAL));
        $value->expects($this->once())
            ->method('setValue')
            ->with($this->equalTo($nodeObject['term'][0]['@value']));
        $value->expects($this->once())
            ->method('setLang')
            ->with($this->equalTo($nodeObject['term'][0]['@language']));
        $value->expects($this->once())
            ->method('setIsHtml')
            ->with($this->equalTo($nodeObject['term'][0]['is_html']));
        $value->expects($this->once())
            ->method('setValueResource')
            ->with($this->equalTo(null));
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Value'),
                $this->equalTo($nodeObject['term'][0]['value_id'])
            )
            ->will($this->returnValue($value));
        $this->adapter->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydrateModifiesResource()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    '@id' => 'test-@id',
                    'value_id' => 'test-value_id',
                    'value_resource_id' => 'test-value_resource_id',
                ),
            ),
        );

        $valueResource = $this->getMock('Omeka\Model\Entity\Resource');
        $value = $this->getMock('Omeka\Model\Entity\Value');
        $value->expects($this->once())
            ->method('setType')
            ->with($this->equalTo(Value::TYPE_RESOURCE));
        $value->expects($this->once())
            ->method('setValue')
            ->with($this->equalTo(null));
        $value->expects($this->once())
            ->method('setLang')
            ->with($this->equalTo(null));
        $value->expects($this->once())
            ->method('setIsHtml')
            ->with($this->equalTo(false));
        $value->expects($this->once())
            ->method('setValueResource')
            ->with($this->identicalTo($valueResource));
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->at(0))
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Value'),
                $this->equalTo($nodeObject['term'][0]['value_id'])
            )
            ->will($this->returnValue($value));
        $entityManager->expects($this->at(1))
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Resource'),
                $this->equalTo($nodeObject['term'][0]['value_resource_id'])
            )
            ->will($this->returnValue($valueResource));
        $this->adapter->expects($this->exactly(2))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydrateModifiesUri()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    '@id' => 'test-@id',
                    'value_id' => 'test-value_id',
                ),
            ),
        );

        $value = $this->getMock('Omeka\Model\Entity\Value');
        $value->expects($this->once())
            ->method('setType')
            ->with($this->equalTo(Value::TYPE_URI));
        $value->expects($this->once())
            ->method('setValue')
            ->with($this->equalTo($nodeObject['term'][0]['@id']));
        $value->expects($this->once())
            ->method('setLang')
            ->with($this->equalTo(null));
        $value->expects($this->once())
            ->method('setIsHtml')
            ->with($this->equalTo(false));
        $value->expects($this->once())
            ->method('setValueResource')
            ->with($this->equalTo(null));
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Value'),
                $this->equalTo($nodeObject['term'][0]['value_id'])
            )
            ->will($this->returnValue($value));
        $this->adapter->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydratePersistsLiteral()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    '@value' => 'test-@value',
                    '@language' => 'test-@language',
                    'property_id' => 'test-property_id',
                    'is_html' => true,
                ),
            ),
        );

        $property = $this->getMock('Omeka\Model\Entity\Property');
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Property'),
                $this->equalTo($nodeObject['term'][0]['property_id'])
            )
            ->will($this->returnValue($property));
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($value) use ($nodeObject) {
                if (!$value->getResource() instanceof Resource) {
                    return false;
                }
                if (!$value->getProperty() instanceof Property) {
                    return false;
                }
                if (Value::TYPE_LITERAL !== $value->getType()) {
                    return false;
                }
                if ($nodeObject['term'][0]['@value'] !== $value->getValue()) {
                    return false;
                }
                if ($nodeObject['term'][0]['@language'] !== $value->getLang()) {
                    return false;
                }
                if ($nodeObject['term'][0]['is_html'] !== $value->isHtml()) {
                    return false;
                }
                return true;
            }));
        $this->adapter->expects($this->exactly(2))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydratePersistsResource()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    'property_id' => 'test-property_id',
                    'value_resource_id' => 'test-value_resource_id',
                ),
            ),
        );

        $valueResource = $this->getMock('Omeka\Model\Entity\Resource');
        $property = $this->getMock('Omeka\Model\Entity\Property');
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->at(0))
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Property'),
                $this->equalTo($nodeObject['term'][0]['property_id'])
            )
            ->will($this->returnValue($property));
        $entityManager->expects($this->at(1))
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Resource'),
                $this->equalTo($nodeObject['term'][0]['value_resource_id'])
            )
            ->will($this->returnValue($valueResource));
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($value) use ($nodeObject) {
                if (!$value->getResource() instanceof Resource) {
                    return false;
                }
                if (!$value->getProperty() instanceof Property) {
                    return false;
                }
                if (Value::TYPE_RESOURCE !== $value->getType()) {
                    return false;
                }
                if (!$value->getValueResource() instanceof Resource) {
                    return false;
                }
                return true;
            }));
        $this->adapter->expects($this->exactly(3))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }

    public function testHydratePersistsUri()
    {
        $nodeObject = array(
            'term' => array(
                array(
                    '@id' => 'test-@id',
                    'property_id' => 'test-property_id',
                ),
            ),
        );

        $property = $this->getMock('Omeka\Model\Entity\Property');
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('getReference')
            ->with(
                $this->equalTo('Omeka\Model\Entity\Property'),
                $this->equalTo($nodeObject['term'][0]['property_id'])
            )
            ->will($this->returnValue($property));
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($value) use ($nodeObject) {
                if (!$value->getResource() instanceof Resource) {
                    return false;
                }
                if (!$value->getProperty() instanceof Property) {
                    return false;
                }
                if (Value::TYPE_URI !== $value->getType()) {
                    return false;
                }
                if ($nodeObject['term'][0]['@id'] !== $value->getValue()) {
                    return false;
                }
                return true;
            }));
        $this->adapter->expects($this->exactly(2))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $hydrator = new ValueHydrator($this->adapter);
        $hydrator->hydrate($nodeObject, $this->resource);
    }
}
