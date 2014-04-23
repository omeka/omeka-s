<?php
namespace OmekaTest\Api\Adapter\Entity;

use Doctrine\ORM\UnitOfWork;
use Omeka\Model\Entity\AbstractEntity;
use Omeka\Test\TestCase;

class AbstractEntityAdapterTest extends TestCase
{
    const TEST_ENTITY_CLASS = 'OmekaTest\Api\Adapter\Entity\TestEntity';

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

        /** ServiceManager **/

        // QueryBuilder
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

        // Service: Omeka\EntityManager
        $entityManager = $this->getEntityManager();
        $entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        // Service: EventManager
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'Omeka\EntityManager' => $entityManager,
            'EventManager' => $eventManager,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/

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

        /** Request **/

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->exactly(3))
            ->method('getContent')
            ->will($this->returnValue($data));

        /** ASSERTIONS **/

        $response = $this->adapter->search($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(100, $response->getTotalResults());
        $this->assertEquals(array('foo', 'bar'), $response->getContent());
    }

    public function testCreate()
    {
        $data = array('foo', 'bar');

        /** ServiceManager **/

        // UnitOfWork
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getEntityState')
            ->will($this->returnValue(UnitOfWork::STATE_MANAGED));

        // Service: Omeka\EntityManager
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'));
        $entityManager->expects($this->once())
            ->method('flush');

        // Service: MvcTranslator
        $translator = $this->getMock('Zend\I18n\Translator\Translator');

        // Service: Omeka\Acl
        $acl = $this->getMock('Omeka\Permissions\Acl');
        $acl->expects($this->once())
            ->method('isAllowed')
            ->with(
                $this->equalTo('current_user'),
                $this->isInstanceOf('OmekaTest\Api\Adapter\Entity\TestEntity'),
                $this->equalTo('create')
            )
            ->will($this->returnValue(true));

        // Service: EventManager
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'Omeka\EntityManager' => $entityManager,
            'MvcTranslator' => $translator,
            'Omeka\Acl' => $acl,
            'EventManager' => $eventManager,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/

        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('extract')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue(array('foo', 'bar')));

        /** Request **/

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($data));

        /** ASSERTIONS **/

        $response = $this->adapter->create($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(array('foo', 'bar'), $response->getContent());
    }

    public function testRead()
    {
        $id = 100;

        /** ServiceManager **/

        // Service: MvcTranslator
        $translator = $this->getMock('Zend\I18n\Translator\Translator');

        // Service: Omeka\EntityManager
        $entityRepostory = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepostory->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(new TestEntity));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo(self::TEST_ENTITY_CLASS))
            ->will($this->returnValue($entityRepostory));

        // Service: Omeka\Acl
        $acl = $this->getMock('Omeka\Permissions\Acl');
        $acl->expects($this->once())
            ->method('isAllowed')
            ->with(
                $this->equalTo('current_user'),
                $this->isInstanceOf('OmekaTest\Api\Adapter\Entity\TestEntity'),
                $this->equalTo('read')
            )
            ->will($this->returnValue(true));

        // Service: EventManager
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'MvcTranslator' => $translator,
            'Omeka\EntityManager' => $entityManager,
            'Omeka\Acl' => $acl,
            'EventManager' => $eventManager,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/

        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('extract')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue(array('foo', 'bar')));

        /** Request **/

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        /** ASSERTIONS **/

        $response = $this->adapter->read($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(array('foo', 'bar'), $response->getContent());
    }

    public function testUpdate()
    {
        $id = 100;
        $data = array('foo', 'bar');

        /** ServiceManager **/

        // Service: MvcTranslator
        $translator = $this->getMock('Zend\I18n\Translator\Translator');

        // UnitOfWork
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getEntityState')
            ->will($this->returnValue(UnitOfWork::STATE_MANAGED));

        // Service: Omeka\EntityManager
        $entityRepostory = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepostory->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(new TestEntity));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo(self::TEST_ENTITY_CLASS))
            ->will($this->returnValue($entityRepostory));
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
        $entityManager->expects($this->once())
            ->method('flush');

        // Service: Omeka\Acl
        $acl = $this->getMock('Omeka\Permissions\Acl');
        $acl->expects($this->once())
            ->method('isAllowed')
            ->with(
                $this->equalTo('current_user'),
                $this->isInstanceOf('OmekaTest\Api\Adapter\Entity\TestEntity'),
                $this->equalTo('update')
            )
            ->will($this->returnValue(true));

        // Service: EventManager
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'MvcTranslator' => $translator,
            'Omeka\EntityManager' => $entityManager,
            'Omeka\Acl' => $acl,
            'EventManager' => $eventManager,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/

        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('hydrate')
            ->with(
                $data, $this->isInstanceOf('Omeka\Model\Entity\EntityInterface')
            );
        $this->adapter->expects($this->once())
            ->method('extract')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue(array('foo', 'bar')));

        /** Request **/

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));
        $request->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($data));

        /** ASSERTIONS **/

        $response = $this->adapter->update($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(array('foo', 'bar'), $response->getContent());
    }
}

class TestEntity extends AbstractEntity
{

}
