<?php
namespace OmekaTest\Api\Representation;

use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Omeka\Test\TestCase;

class ValueRepresentationTest extends TestCase
{
    public function testToStringResource()
    {}

    public function testToStringUri()
    {}

    public function testToStringLiteral()
    {
        $literal = 'test_literal';

        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Value::TYPE_LITERAL));
        $value->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($literal));

        $valueRep = new ValueRepresentation($value, $this->getServiceManager());
        $this->assertEquals($literal, $valueRep->__toString());
    }

    public function testValidateData()
    {
        $data = 'invalid_data';
        $serviceLocator = $this->getServiceManager(array(
            'MvcTranslator' => $this->getMock('Zend\I18n\Translator\TranslatorInterface'),
        ));

        $this->setExpectedException('Omeka\Api\Exception\InvalidArgumentException');
        $valueRep = new ValueRepresentation($data, $serviceLocator);
    }

    public function testJsonSerializeResource()
    {
        $valueResourceName   = 'test-value_resource_name';
        $valueResourceId     = 'test-value_resource_id';
        $valueResourceApiUrl = 'test-value_resource_api_url';
        $valueId             = 'test-value_id';
        $propertyId          = 'test-property_id';
        $propertyLabel       = 'test-property_label';

        $resourceRep = $this->getMock(
            'Omeka\Api\Representation\AbstractResourceEntityRepresentation',
            array('getJsonLd', 'valueRepresentation', 'getResourceJsonLd'), array(), '',
            false
        );
        $resourceRep->expects($this->once())
            ->method('valueRepresentation')
            ->will($this->returnValue(array(
                'fake_value_representation' => 'fake_data'
            )));

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->expects($this->once())
            ->method('getRepresentation')
            ->with($valueResourceId)
            ->will($this->returnValue($resourceRep));

        $apiAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $apiAdapterManager->expects($this->once())
            ->method('get')
            ->with($this->equalTo($valueResourceName))
            ->will($this->returnValue($adapter));

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
        ));

        $valueResource = $this->getMockForAbstractClass(
            'Omeka\Entity\Resource',
            array(), '', true, true, true, array('getId'), false
        );
        $valueResource->expects($this->once())
            ->method('getResourceName')
            ->will($this->returnValue($valueResourceName));
        $valueResource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($valueResourceId));

        $property = $this->getMock('Omeka\Entity\Property');
        $property->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($propertyId));
        $property->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue($propertyLabel));

        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Value::TYPE_RESOURCE));
        $value->expects($this->once())
            ->method('getValueResource')
            ->will($this->returnValue($valueResource));
        $value->expects($this->exactly(2))
            ->method('getProperty')
            ->will($this->returnValue($property));
        $value->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($valueId));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertEquals(array(
            'fake_value_representation' => 'fake_data',
            'value_id'          => $valueId,
            'property_id'       => $propertyId,
            'property_label'    => $propertyLabel,
        ), $valueRep->jsonSerialize());
    }

    public function testJsonSerializeUri()
    {
        $valueResourceApiUrl = 'test-value_resource_api_url';
        $valueId             = 'test-value_id';
        $valueUri            = 'test-value_uri';
        $propertyId          = 'test-property_id';
        $propertyLabel       = 'test-property_label';

        $serviceLocator = $this->getServiceManager();

        $property = $this->getMock('Omeka\Entity\Property');
        $property->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($propertyId));
        $property->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue($propertyLabel));

        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Value::TYPE_URI));
        $value->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($valueUri));
        $value->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($valueId));
        $value->expects($this->exactly(2))
            ->method('getProperty')
            ->will($this->returnValue($property));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertEquals(array(
            '@id'            => $valueUri,
            'value_id'       => $valueId,
            'property_id'    => $propertyId,
            'property_label' => $propertyLabel,
        ), $valueRep->jsonSerialize());
    }

    public function testJsonSerializeLiteral()
    {
        $valueResourceApiUrl = 'test-value_resource_api_url';
        $valueId             = 'test-value_id';
        $valueLiteral        = 'test-value_literal';
        $valueLang           = 'test-value_lang';
        $propertyId          = 'test-property_id';
        $propertyLabel       = 'test-property_label';

        $serviceLocator = $this->getServiceManager();

        $property = $this->getMock('Omeka\Entity\Property');
        $property->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($propertyId));
        $property->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue($propertyLabel));

        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Value::TYPE_LITERAL));
        $value->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($valueLiteral));
        $value->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($valueId));
        $value->expects($this->exactly(2))
            ->method('getLang')
            ->will($this->returnValue($valueLang));
        $value->expects($this->exactly(2))
            ->method('getProperty')
            ->will($this->returnValue($property));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertEquals(array(
            '@value'         => $valueLiteral,
            '@language'      => $valueLang,
            'value_id'       => $valueId,
            'property_id'    => $propertyId,
            'property_label' => $propertyLabel,
        ), $valueRep->jsonSerialize());
    }

    public function testGetType()
    {
        $valueType = 'test-value_type';

        $serviceLocator = $this->getServiceManager();
        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($valueType));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertEquals($valueType, $valueRep->type());
    }

    public function testGetValue()
    {
        $valueValue = 'test-value_value';

        $serviceLocator = $this->getServiceManager();
        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($valueValue));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertEquals($valueValue, $valueRep->value());
    }

    public function testGetLang()
    {
        $valueLang = 'test-value_lang';

        $serviceLocator = $this->getServiceManager();
        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getLang')
            ->will($this->returnValue($valueLang));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertEquals($valueLang, $valueRep->lang());
    }

    public function testGetValueResource()
    {
        $valueResourceName = 'test-value_resource_name';
        $valueResourceId = 'test-value_resource_id';

        $valueResource = $this->getMockForAbstractClass(
            'Omeka\Entity\Resource',
            array(), '', true, true, true, array('getId'), false
        );
        $valueResource->expects($this->once())
            ->method('getResourceName')
            ->will($this->returnValue($valueResourceName));
        $valueResource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($valueResourceId));

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->expects($this->once())
            ->method('getRepresentation')
            ->with(
                $this->equalTo($valueResourceId),
                $this->identicalTo($valueResource)
            );

        $apiAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $apiAdapterManager->expects($this->once())
            ->method('get')
            ->with($this->equalTo($valueResourceName))
            ->will($this->returnValue($adapter));

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
        ));

        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getValueResource')
            ->will($this->returnValue($valueResource));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertNull($valueRep->valueResource());
    }

    public function testGetResource()
    {
        $resourceName = 'test-value_resource_name';
        $resourceId = 'test-value_resource_id';

        $resource = $this->getMockForAbstractClass(
            'Omeka\Entity\Resource',
            array(), '', true, true, true, array('getId'), false
        );
        $resource->expects($this->once())
            ->method('getResourceName')
            ->will($this->returnValue($resourceName));

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->expects($this->once())
            ->method('getRepresentation')
            ->with(
                $this->isNull(),
                $this->identicalTo($resource)
            );

        $apiAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $apiAdapterManager->expects($this->once())
            ->method('get')
            ->with($this->equalTo($resourceName))
            ->will($this->returnValue($adapter));

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
        ));

        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue($resource));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertNull($valueRep->resource());
    }

    public function testGetProperty()
    {
        $propertyId = 'test-property_id';

        $property = $this->getMock('Omeka\Entity\Property');

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');
        $adapter->expects($this->once())
            ->method('getRepresentation')
            ->with(
                $this->isNull(),
                $this->identicalTo($property)
            );

        $apiAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $apiAdapterManager->expects($this->once())
            ->method('get')
            ->with($this->equalTo('properties'))
            ->will($this->returnValue($adapter));

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
        ));

        $value = $this->getMock('Omeka\Entity\Value');
        $value->expects($this->once())
            ->method('getProperty')
            ->will($this->returnValue($property));

        $valueRep = new ValueRepresentation($value, $serviceLocator);
        $this->assertNull($valueRep->property());
    }
}
