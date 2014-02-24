<?php
namespace OmekaTest\Model;

class AbstractEntityTest extends \PHPUnit_Framework_TestCase
{
    public function testGetsResourceId()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Model\Entity\AbstractEntity');
        $this->assertSame(
            strpos($adapter->getResourceId(), 'Mock_AbstractEntity_'),
            0
        );
    }
}
