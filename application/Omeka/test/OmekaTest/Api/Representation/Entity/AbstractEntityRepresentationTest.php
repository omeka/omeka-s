<?php
namespace OmekaTest\Api\Representation;

use Omeka\Test\TestCase;

class AbstractEntityRepresentationTest extends TestCase
{
    public function testValidates()
    {
        $this->setExpectedException('Omeka\Api\Exception\InvalidArgumentException');

        $id = 'test-id';
        $data = 'test-data';
        $mockAdapter = $this->getMock(
            'Omeka\Api\Adapter\AbstractAdapter'
        );

        $mockServiceManager = $this->getServiceManager(array(
            'MvcTranslator' => $this->getMock(
                'Zend\Mvc\I18n\Translator', array(), array(), '', false
            ),
        ));
        $mockAdapter->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($mockServiceManager));

        $mockRep = $this->getMockForAbstractClass(
            'Omeka\Api\Representation\Entity\AbstractEntityRepresentation',
            array($id, $data, $mockAdapter), '', true, true, true, array()
        );
    }
}
