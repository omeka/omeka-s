<?php
namespace OmekaTest\Api\Adapter\Entity;

use Doctrine\ORM\UnitOfWork;
use Omeka\Api\Representation\RepresentationInterface;
use Omeka\Model\Entity\AbstractEntity;
use Omeka\Test\TestCase;
use Zend\ServiceManager\ServiceLocatorInterface;

class AbstractEntityAdapterTest extends TestCase
{
    const TEST_ENTITY_CLASS = 'OmekaTest\Api\Adapter\Entity\TestEntity';

    protected $adapter;

    public function setUp()
    {
        $this->adapter = $this->getMock(
            'Omeka\Api\Adapter\Entity\AbstractEntityAdapter',
            array('hydrate', 'getResourceName', 'getRepresentationClass',
                'getEntityClass', 'getEventManager')
        );
    }

    public function testSearch()
    {}

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
            ->method('userIsAllowed')
            ->with(
                $this->isInstanceOf('OmekaTest\Api\Adapter\Entity\TestEntity'),
                $this->equalTo('create')
            )
            ->will($this->returnValue(true));

        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'Omeka\EntityManager' => $entityManager,
            'MvcTranslator' => $translator,
            'Omeka\Acl' => $acl,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/
        $this->adapter->expects($this->once())
             ->method('getEventManager')
             ->will($this->returnValue($eventManager));
        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('getRepresentationClass')
            ->will($this->returnValue('OmekaTest\Api\Adapter\Entity\TestRepresentation'));

        /** Request **/

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($data));

        /** ASSERTIONS **/

        $response = $this->adapter->create($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertInstanceOf(
            'OmekaTest\Api\Adapter\Entity\TestRepresentation',
            $response->getContent()
        );
    }

    public function testRead()
    {
        $id = 100;

        /** ServiceManager **/

        // Service: MvcTranslator
        $translator = $this->getMock('Zend\I18n\Translator\Translator');

        // Service: Omeka\EntityManager
        $entityRepository = $this->getEntityRepository();
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(array('id' => $id)))
            ->will($this->returnValue(new TestEntity));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('OmekaTest\Api\Adapter\Entity\TestEntity'))
            ->will($this->returnValue($entityRepository));

        // Service: Omeka\Acl
        $acl = $this->getMock('Omeka\Permissions\Acl');
        $acl->expects($this->once())
            ->method('userIsAllowed')
            ->with(
                $this->isInstanceOf('OmekaTest\Api\Adapter\Entity\TestEntity'),
                $this->equalTo('read')
            )
            ->will($this->returnValue(true));

        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'MvcTranslator' => $translator,
            'Omeka\EntityManager' => $entityManager,
            'Omeka\Acl' => $acl,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/
        $this->adapter->expects($this->once())
             ->method('getEventManager')
             ->will($this->returnValue($eventManager));
        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('getRepresentationClass')
            ->will($this->returnValue('OmekaTest\Api\Adapter\Entity\TestRepresentation'));

        /** Request **/

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        /** ASSERTIONS **/

        $response = $this->adapter->read($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertInstanceOf(
            'OmekaTest\Api\Adapter\Entity\TestRepresentation',
            $response->getContent()
        );
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
        $entityRepository = $this->getEntityRepository();
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(array('id' => $id)))
            ->will($this->returnValue(new TestEntity));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('OmekaTest\Api\Adapter\Entity\TestEntity'))
            ->will($this->returnValue($entityRepository));
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
        $entityManager->expects($this->once())
            ->method('flush');

        // Service: Omeka\Acl
        $acl = $this->getMock('Omeka\Permissions\Acl');
        $acl->expects($this->once())
            ->method('userIsAllowed')
            ->with(
                $this->isInstanceOf('OmekaTest\Api\Adapter\Entity\TestEntity'),
                $this->equalTo('update')
            )
            ->will($this->returnValue(true));

        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'MvcTranslator' => $translator,
            'Omeka\EntityManager' => $entityManager,
            'Omeka\Acl' => $acl,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/
        $this->adapter->expects($this->once())
             ->method('getEventManager')
             ->will($this->returnValue($eventManager));
        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('hydrate')
            ->with(
                $data, $this->isInstanceOf('Omeka\Model\Entity\EntityInterface')
            );
        $this->adapter->expects($this->once())
            ->method('getRepresentationClass')
            ->will($this->returnValue('OmekaTest\Api\Adapter\Entity\TestRepresentation'));

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
        $this->assertInstanceOf(
            'OmekaTest\Api\Adapter\Entity\TestRepresentation',
            $response->getContent()
        );
    }

    public function testDelete()
    {
        $id = 100;

        /** ServiceManager **/

        // Service: MvcTranslator
        $translator = $this->getMock('Zend\I18n\Translator\Translator');

        // Service: Omeka\EntityManager
        $entityRepository = $this->getEntityRepository();
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(array('id' => $id)))
            ->will($this->returnValue(new TestEntity));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('OmekaTest\Api\Adapter\Entity\TestEntity'))
            ->will($this->returnValue($entityRepository));
        $entityManager->expects($this->once())
            ->method('remove')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'));
        $entityManager->expects($this->once())
            ->method('flush');

        // Service: Omeka\Acl
        $acl = $this->getMock('Omeka\Permissions\Acl');
        $acl->expects($this->once())
            ->method('userIsAllowed')
            ->with(
                $this->isInstanceOf('OmekaTest\Api\Adapter\Entity\TestEntity'),
                $this->equalTo('delete')
            )
            ->will($this->returnValue(true));

        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));

        $serviceManager = $this->getServiceManager(array(
            'MvcTranslator' => $translator,
            'Omeka\EntityManager' => $entityManager,
            'Omeka\Acl' => $acl,
        ));
        $this->adapter->setServiceLocator($serviceManager);

        /** Adapter **/
        $this->adapter->expects($this->once())
             ->method('getEventManager')
             ->will($this->returnValue($eventManager));
        $this->adapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(self::TEST_ENTITY_CLASS));
        $this->adapter->expects($this->once())
            ->method('getRepresentationClass')
            ->will($this->returnValue('OmekaTest\Api\Adapter\Entity\TestRepresentation'));

        /** Request **/

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        /** ASSERTIONS **/

        $response = $this->adapter->delete($request);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('success', $response->getStatus());
        $this->assertInstanceOf(
            'OmekaTest\Api\Adapter\Entity\TestRepresentation',
            $response->getContent()
        );
    }
}

class TestEntity extends AbstractEntity
{
    public function getId(){}
}

class TestRepresentation implements RepresentationInterface
{
    public function setData($data){}
    public function jsonSerialize(){}
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator){}
    public function getServiceLocator(){}
}
