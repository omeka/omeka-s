<?php
namespace OmekaTest\Api;

use Omeka\Test\MockBuilder;

class AbstractEntityAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\Entity\AbstractEntityAdapter'
        );
    }

    public function testSearches()
    {
        // Set default data.
        $data = array(
            'sort_by' => 'sortby',
            'sort_order' => 'desc',
            'limit' => 10,
            'offset' => 20,
        );
        $entityClass = 'Foo';
        $totalResults = 100;
        $iterateOutput = array(
            array('row_one'),
            array('row_two'),
        );

        // Build mock objects.
        $mockBuilder = new MockBuilder;
        $query = $mockBuilder->getQuery(array('getSingleScalarResult', 'iterate'));
        $queryExpr = $this->getMock('Doctrine\ORM\Query\Expr');
        $queryBuilder = $mockBuilder->getQueryBuilder();
        $entityManager = $mockBuilder->getEntityManager();
        $serviceManager = $mockBuilder->getServiceManager(array('EntityManager' => $entityManager));
        $eventManager = $this->getMock('Zend\EventManager\EventManager');

        // EntityManager expectations
        $entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        // QueryBuilder expectations
        $queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->with($this->logicalOr(
                $this->equalTo($entityClass),
                $this->equalTo(null)
            ))
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('from')
            ->with($this->equalTo($entityClass), $this->equalTo($entityClass));
        $queryBuilder->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($queryExpr));
        $queryBuilder->expects($this->exactly(2))
            ->method('getQuery')
            ->will($this->returnValue($query));
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with($this->equalTo(
                $entityClass . '.' . $data['sort_by']),
                $this->equalTo('DESC')
            );
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with($this->equalTo($data['limit']));
        $queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->with($this->equalTo($data['offset']));

        // Query expectations
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->will($this->returnValue($totalResults));
        $query->expects($this->once())
            ->method('iterate')
            ->will($this->returnValue($iterateOutput));

        // Expr expactations
        $queryExpr->expects($this->once())
            ->method('count')
            ->with($this->equalTo("$entityClass.id"));

        // AbstractEntityAdapter expectations.
        $this->adapter->expects($this->exactly(2))
            ->method('getEntityClass')
            ->will($this->returnValue($entityClass));
        $this->adapter->expects($this->once())
            ->method('buildQuery')
            ->with($this->equalTo($data), $this->identicalTo($queryBuilder));
        $this->adapter->expects($this->exactly(2))
            ->method('extract')
            ->will($this->returnArgument(0));

        $this->adapter->setRequest($this->getMock('Omeka\Api\Request'));
        $this->adapter->setServiceLocator($serviceManager);
        $this->adapter->setEventManager($eventManager);
        $response = $this->adapter->search($data);
        $this->assertEquals(array('row_one', 'row_two'), $response->getContent());
        $this->assertEquals($totalResults, $response->getTotalResults());
    }

    public function testCreates()
    {
    }

    public function testCreateHandlesValidationError()
    {
    }

    public function testReads()
    {
    }

    public function testReadHandlesNotFoundError()
    {
    }

    public function testUpdates()
    {
    }

    public function testUpdateHandlesNotFoundErrors()
    {
    }

    public function testUpdateHandlesValidationErrors()
    {
    }

    public function testDeletes()
    {
    }

    public function testDeleteHandlesNotFoundErrors()
    {
    }
}
