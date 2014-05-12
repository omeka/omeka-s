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

    protected $testVocabulary = array(
        'prefix'        => 'testvocabulary-prefix',
        'namespace_uri' => 'testvocabulary-namespace_uri',
        'id'            => 'testvocabulary-id',
        'label'         => 'testvocabulary-label',
    );
    protected $testProperty = array(
        'local_name' => 'testproperty-local_name',
        'id'         => 'testproperty-id',
        'label'      => 'testproperty-label',
    );
    protected $testValue = array(
        'id'      => 'testvalue-id',
        'value'   => 'testvalue-value',
        'lang'    => 'testvalue-lang',
        'is_html' => 'testvalue-is_html',
    );
    protected $testValueResource = array(
        'resource_name' => 'testvalueresource-resource_name',
        '@id'           => 'testvalueresource-@id',
        'id'            => 'testvalueresource-id',
    );

    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\Entity\AbstractEntityAdapter',
                array(), '', true, true, true,
                array('getEntityManager', 'getAdapter', 'getApiUrl')
        );
        $this->resource = $this->getMock('Omeka\Model\Entity\Resource');
    }

    public function testExtractsResource()
    {
        list($vocabulary, $property, $value) = $this->getMockObjectsForExtract();

        $valueResource = $this->getMock('Omeka\Model\Entity\Resource');
        $valueResource->expects($this->once())
            ->method('getResourceName')
            ->will($this->returnValue($this->testValueResource['resource_name']));
        $valueResource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($this->testValueResource['id']));

        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Value::TYPE_RESOURCE));
        $value->expects($this->once())
            ->method('getValueResource')
            ->will($this->returnValue($valueResource));

        $this->adapter->expects($this->once())
            ->method('getAdapter')
            ->with($this->equalTo($this->testValueResource['resource_name']))
            ->will($this->returnValue($this->adapter));
        $this->adapter->expects($this->once())
            ->method('getApiUrl')
            ->with($this->equalTo($valueResource))
            ->will($this->returnValue($this->testValueResource['@id']));

        $this->resource->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue(array($value)));

        $hydrator = new ValueHydrator($this->adapter);
        $extractedValue = $hydrator->extract($this->resource);

        $term = $this->testVocabulary['prefix'] . ':' . $this->testProperty['local_name'];
        $valueObject = array(
            array(
                '@id'               => $this->testValueResource['@id'],
                'value_resource_id' => $this->testValueResource['id'],
                'value_id'          => $this->testValue['id'],
                'property_id'       => $this->testProperty['id'],
                'property_label'    => $this->testProperty['label'],
            ),
        );
        $this->assertEquals($valueObject, $extractedValue[$term]);
        $this->assertContextObject($extractedValue);
    }

    public function testExtractsUri()
    {
        list($vocabulary, $property, $value) = $this->getMockObjectsForExtract();

        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Value::TYPE_URI));
        $value->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($this->testValue['value']));

        $this->resource->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue(array($value)));

        $hydrator = new ValueHydrator($this->adapter);
        $extractedValue = $hydrator->extract($this->resource);

        $term = $this->testVocabulary['prefix'] . ':' . $this->testProperty['local_name'];
        $valueObject = array(
            array(
                '@id'               => $this->testValue['value'],
                'value_id'          => $this->testValue['id'],
                'property_id'       => $this->testProperty['id'],
                'property_label'    => $this->testProperty['label'],
            ),
        );
        $this->assertEquals($valueObject, $extractedValue[$term]);
        $this->assertContextObject($extractedValue);
    }

    public function testExtractsLiteral()
    {
        list($vocabulary, $property, $value) = $this->getMockObjectsForExtract();

        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Value::TYPE_LITERAL));
        $value->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($this->testValue['value']));
        $value->expects($this->exactly(2))
            ->method('getLang')
            ->will($this->returnValue($this->testValue['lang']));
        $value->expects($this->once())
            ->method('getIsHtml')
            ->will($this->returnValue($this->testValue['is_html']));

        $this->resource->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue(array($value)));

        $hydrator = new ValueHydrator($this->adapter);
        $extractedValue = $hydrator->extract($this->resource);

        $term = $this->testVocabulary['prefix'] . ':' . $this->testProperty['local_name'];
        $valueObject = array(
            array(
                '@value'         => $this->testValue['value'],
                '@language'      => $this->testValue['lang'],
                'is_html'        => $this->testValue['is_html'],
                'value_id'       => $this->testValue['id'],
                'property_id'    => $this->testProperty['id'],
                'property_label' => $this->testProperty['label'],
            ),
        );
        $this->assertEquals($valueObject, $extractedValue[$term]);
        $this->assertContextObject($extractedValue);
    }

    /**
     * Assert the context object for extract tests.
     *
     * @param array $extractedValue
     */
    protected function assertContextObject(array $extractedValue)
    {
        $contextObject = array(
            $this->testVocabulary['prefix'] => array(
                '@id'              => $this->testVocabulary['namespace_uri'],
                'vocabulary_id'    => $this->testVocabulary['id'],
                'vocabulary_label' => $this->testVocabulary['label'],
            ),
        );
        $this->assertEquals($contextObject, $extractedValue['@context']);
    }

    /**
     * Get mock objects prepared for common use in extract tests.
     *
     * @return array Use list($vocabulary, $property, $value) in tests
     */
    protected function getMockObjectsForExtract()
    {
        $vocabulary = $this->getMock('Omeka\Model\Entity\Vocabulary');
        $vocabulary->expects($this->once())
            ->method('getPrefix')
            ->will($this->returnValue($this->testVocabulary['prefix']));
        $vocabulary->expects($this->once())
            ->method('getNamespaceUri')
            ->will($this->returnValue($this->testVocabulary['namespace_uri']));
        $vocabulary->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($this->testVocabulary['id']));
        $vocabulary->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue($this->testVocabulary['label']));

        $property = $this->getMock('Omeka\Model\Entity\Property');
        $property->expects($this->once())
            ->method('getVocabulary')
            ->will($this->returnValue($vocabulary));
        $property->expects($this->once())
            ->method('getLocalName')
            ->will($this->returnValue($this->testProperty['local_name']));
        $property->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($this->testProperty['id']));
        $property->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue($this->testProperty['label']));

        $value = $this->getMock('Omeka\Model\Entity\Value');
        $value->expects($this->once())
            ->method('getProperty')
            ->will($this->returnValue($property));
        $value->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($this->testValue['id']));

        return array($vocabulary, $property, $value);
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
                if ($nodeObject['term'][0]['is_html'] !== $value->getIsHtml()) {
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
