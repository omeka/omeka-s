<?php
namespace OmekaTest\Api\Reference;

use Omeka\Api\Reference\Entity;
use Omeka\Test\TestCase;

class EntityTest extends TestCase
{
    protected $reference;
    protected $testToArray = array('foo', 'bar');

    public function setUp()
    {
        $this->reference = new Entity;
    }

    public function testSetDataRequiresValidEntity()
    {
        $this->setExpectedException('Omeka\Api\Exception\InvalidArgumentException');
        $this->reference->setData(new \stdClass);
    }

    public function testToArray()
    {
        $this->reference->setData($this->getMock('Omeka\Model\Entity\EntityInterface'));
        $this->reference->setAdapter($this->getMockAdapter());
        $this->assertEquals($this->testToArray, $this->reference->toArray());
    }

    public function getMockAdapter()
    {
        $mockAdapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\Entity\AbstractEntityAdapter'
        );
        $mockAdapter->expects($this->any())
            ->method('extract')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue($this->testToArray));
        return $mockAdapter;
    }
}
