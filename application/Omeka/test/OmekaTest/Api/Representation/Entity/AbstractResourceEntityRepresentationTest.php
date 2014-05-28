<?php
namespace OmekaTest\Api\Representation;

use Omeka\Test\TestCase;

class AbstractResourceEntityRepresentationTest extends TestCase
{
    public function testValidates()
    {
        $this->setExpectedException('Omeka\Api\Exception\InvalidArgumentException');

        $id = 'test-id';
        $data = 'test-invalid-data';
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');

        $mockServiceManager = $this->getServiceManager(array(
            'MvcTranslator' => $this->getMock(
                'Zend\Mvc\I18n\Translator', array(), array(), '', false
            ),
        ));
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));

        $mockRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($id, $data, $mockAdapter), '', true, true, true, array()
        );
    }

    public function testJsonSerializeResource()
    {
        $id = 'test-id';
        $mockResource = $this->getMockForAbstractClass('Omeka\Model\Entity\Resource');
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AbstractAdapter');

        $mockServiceManager = $this->getServiceManager();
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));

        $mockRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractResourceEntityRepresentation',
            array($id, $mockResource, $mockAdapter), '', true, true, true, array()
        );

        $this->assertEquals(array(), $mockRep->jsonSerializeResource());
    }
}
