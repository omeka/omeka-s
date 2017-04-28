<?php
namespace OmekaTest\Api\Adapter\Entity;

use Omeka\Test\TestCase;

class AbstractResourceEntityAdapterTest extends TestCase
{
    public function testBuildQuery()
    {
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query = ['foo', 'bar'];
        $adapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\AbstractResourceEntityAdapter',
            [], '', true, true, true,
            ['buildPropertyQuery', 'buildHasPropertyQuery']
        );
        $adapter->expects($this->once())
            ->method('buildPropertyQuery')
            ->with($this->equalTo($queryBuilder), $this->equalTo($query));
        $adapter->buildQuery($queryBuilder, $query);
    }
}
