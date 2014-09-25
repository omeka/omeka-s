<?php
namespace OmekaTest\Api\Representation\Entity;

use Omeka\Test\TestCase;

class AbstractResourceEntityRepresentationTest extends TestCase
{
    public function testGetJsonLd()
    {
        $resourceName           = 'test-resource_name';
        $resourceApiUrl         = 'test-resource_api_url';
        $resourceId             = 'test-resource_id';
        $resourceCreated        = new \DateTime;
        $resourceModified       = new \DateTime;
        $resourceJsonLd         = array('resource_json_ld' => 'test-resource_json_ld');
        $resourceClassLocalName = 'test-resource_class_local_name';
        $vocabularyPrefix       = 'test-vocubulary_prefix';
        $valueVocabularyPrefix  = 'test-value_vocabulary_prefix';
        $valuePropertyLocalName = 'test-value_property_local_name';

        $vocabulary = $this->getMock('Omeka\Model\Entity\Vocabulary');
        $vocabulary->expects($this->exactly(2))
            ->method('getPrefix')
            ->will($this->returnValue($vocabularyPrefix));

        $resourceClass = $this->getMock('Omeka\Model\Entity\ResourceClass');
        $resourceClass->expects($this->once())
            ->method('getVocabulary')
            ->will($this->returnValue($vocabulary));
        $resourceClass->expects($this->once())
            ->method('getLocalName')
            ->will($this->returnValue($resourceClassLocalName));

        $valueVocabulary = $this->getMock('Omeka\Model\Entity\Vocabulary');
        $valueVocabulary->expects($this->exactly(2))
            ->method('getPrefix')
            ->will($this->returnValue($valueVocabularyPrefix));

        $valueProperty = $this->getMock('Omeka\Model\Entity\Property');
        $valueProperty->expects($this->once())
            ->method('getVocabulary')
            ->will($this->returnValue($valueVocabulary));
        $valueProperty->expects($this->once())
            ->method('getLocalName')
            ->will($this->returnValue($valuePropertyLocalName));

        $value = $this->getMock('Omeka\Model\Entity\Value');
        $value->expects($this->once())
            ->method('getProperty')
            ->will($this->returnValue($valueProperty));

        $owner = $this->getMock('Omeka\Model\Entity\User');

        $resource = $this->getMock('Omeka\Model\Entity\Resource');
        $resource->expects($this->exactly(3))
            ->method('getResourceClass')
            ->will($this->returnValue($resourceClass));
        $resource->expects($this->once())
            ->method('getOwner')
            ->will($this->returnValue($owner));
        $resource->expects($this->once())
            ->method('getCreated')
            ->will($this->returnValue($resourceCreated));
        $resource->expects($this->exactly(2))
            ->method('getModified')
            ->will($this->returnValue($resourceModified));
        $resource->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue(array($value)));

        $childAdapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');

        $apiAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $apiAdapterManager->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue($childAdapter));

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
        ));

        $childAdapter->expects($this->exactly(2))
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $adapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($resourceId, $resource, $adapter)
        );
        $abstractResourceEntityRep->expects($this->once())
            ->method('getResourceJsonLd')
            ->will($this->returnValue($resourceJsonLd));

        $jsonLd = $abstractResourceEntityRep->getJsonLd();
        $this->assertEquals("$vocabularyPrefix:$resourceClassLocalName", $jsonLd['@type']);
        $this->assertInstanceOf('Omeka\Api\Representation\ResourceReference', $jsonLd['o:owner']);
        $this->assertInstanceOf('Omeka\Stdlib\DateTime', $jsonLd['o:created']['@value']);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema#dateTime', $jsonLd['o:created']['@type']);
        $this->assertInstanceOf('Omeka\Stdlib\DateTime', $jsonLd['o:modified']['@value']);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema#dateTime', $jsonLd['o:modified']['@type']);
        $this->assertEquals($resourceJsonLd['resource_json_ld'], $jsonLd['resource_json_ld']);
        $this->assertInstanceOf('Omeka\Api\Representation\ValueRepresentation', $jsonLd["$valueVocabularyPrefix:$valuePropertyLocalName"][0]);
    }

    public function testGetResourceClass()
    {
        $resourceId = 'test-resource_id';

        $resourceClass = $this->getMock('Omeka\Model\Entity\ResourceClass');

        $resource = $this->getMock('Omeka\Model\Entity\Resource');
        $resource->expects($this->once())
            ->method('getResourceClass')
            ->will($this->returnValue($resourceClass));

        $resourceClassAdapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $resourceClassAdapter->expects($this->once())
            ->method('getRepresentation')
            ->with(
                $this->equalTo(null),
                $this->equalTo($resourceClass)
            );

        $apiAdapterManager = $this->getMock('Omeka\Api\Adapter\Manager');
        $apiAdapterManager->expects($this->once())
            ->method('get')
            ->will($this->returnValue($resourceClassAdapter));

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\ApiAdapterManager' => $apiAdapterManager,
        ));

        $adapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($resourceId, $resource, $adapter)
        );
        $this->assertNull($abstractResourceEntityRep->getResourceClass());
    }

    public function testGetCreated()
    {
        $resourceId = 'test-resource_id';
        $resourceCreated = 'test-resource_created';

        $resource = $this->getMock('Omeka\Model\Entity\Resource');
        $resource->expects($this->once())
            ->method('getCreated')
            ->will($this->returnValue($resourceCreated));

        $serviceLocator = $this->getServiceManager();

        $adapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($resourceId, $resource, $adapter)
        );
        $this->assertEquals($resourceCreated, $abstractResourceEntityRep->getCreated());
    }

    public function testGetModified()
    {
        $resourceId = 'test-resource_id';
        $resourceModified = 'test-resource_modified';

        $resource = $this->getMock('Omeka\Model\Entity\Resource');
        $resource->expects($this->once())
            ->method('getModified')
            ->will($this->returnValue($resourceModified));

        $serviceLocator = $this->getServiceManager();

        $adapter = $this->getMock('Omeka\Api\Adapter\Entity\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $abstractResourceEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($resourceId, $resource, $adapter)
        );
        $this->assertEquals($resourceModified, $abstractResourceEntityRep->getModified());
    }
}
