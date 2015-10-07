<?php
namespace OmekaTest\Api\Representation\Entity;

use Omeka\Test\TestCase;

class AbstractEntityRepresentationTest extends TestCase
{
    public function testValidateData()
    {
        $serviceLocator = $this->getServiceManager([
            'MvcTranslator' => $this->getMock('Zend\I18n\Translator\TranslatorInterface'),
        ]);

        $adapter = $this->getMock('Omeka\Api\Adapter\AbstractEntityAdapter');
        $adapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $this->setExpectedException('Omeka\Api\Exception\InvalidArgumentException');
        $abstractEntityRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\AbstractEntityRepresentation',
            ['id', 'invalid_data', $adapter]
        );
    }
}
