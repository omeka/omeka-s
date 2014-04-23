<?php
namespace OmekaTest\Api\Adapter\Entity;

use Omeka\Test\TestCase;

class AbstractEntityAdapterTest extends TestCase
{
    const TEST_ENTITY_CLASS = 'TestEntityClass';

    protected $adapter;

    public function setUp()
    {
        $this->adapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\Entity\AbstractEntityAdapter'
        );
    }

    public function testSearch()
    {
        $data = array('foo', 'bar');
        $totalResults = 100;
        $iterateRows = array(array('foo'), array('bar'));

        // ServiceManager
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('select', 'from', 'expr', 'count', 'getQuery',
                'getSingleScalarResult', 'iterate'))
            ->getMock();
        $queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('from')
            ->with(
                $this->equalTo(self::TEST_ENTITY_CLASS),
                $this->equalTo(self::TEST_ENTITY_CLASS)
            )
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('expr')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('count')
            ->with($this->equalTo(self::TEST_ENTITY_CLASS . '.id'));
        $queryBuilder->expects($this->exactly(2))
            ->method('getQuery')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getSingleScalarResult')
            ->will($this->returnValue($totalResults));
        $queryBuilder->expects($this->once())
            ->method('iterate')
            ->will($this->returnValue($iterateRows));

        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'Omeka\EntityManager' => $entityManager,
            'EventManager' => $eventManager,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        // Adapter
        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('buildQuery')
            ->with(
                $this->equalTo($data),
                $this->isInstanceOf('Doctrine\ORM\QueryBuilder')
            );
        $this->adapter->expects($this->exactly(2))
            ->method('extract')
            ->with($this->callback(function ($subject) use ($iterateRows) {
                return in_array(
                    $subject,
                    array($iterateRows[0][0], $iterateRows[1][0])
                );
            }))
            ->will($this->returnArgument(0));

        // Request
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->exactly(3))
            ->method('getContent')
            ->will($this->returnValue($data));

        $response = $this->adapter->search($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(100, $response->getTotalResults());
        $this->assertEquals(array('foo', 'bar'), $response->getContent());
    }
}
