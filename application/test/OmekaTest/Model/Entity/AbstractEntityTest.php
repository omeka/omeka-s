<?php
namespace OmekaTest\Model;

use Omeka\Test\TestCase;

class AbstractEntityTest extends TestCase
{
    public function testGetsResourceId()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Entity\AbstractEntity');
        $this->assertSame(
            strpos($adapter->getResourceId(), 'Mock_AbstractEntity_'),
            0
        );
    }
}
