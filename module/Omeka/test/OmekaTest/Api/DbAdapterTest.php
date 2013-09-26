<?php
namespace OmekaTest\Api;

use Omeka\Model\Entity\EntityInterface;
use Omeka\Api\Adapter\Db;

class DbAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $dbAdapter;

    public function setUp()
    {
        $this->dbAdapter = new Db;
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testSetDataRequiresEntityClassConfig()
    {
        $dbAdapter = new Db;
        $dbAdapter->setData(array());
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testSetDataRequiresEntityClass()
    {
        $dbAdapter = new Db;
        $dbAdapter->setData(array('entity_class' => 'foo'));
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testSetDataRequiresEntityClassEntityInterface()
    {
        $dbAdapter = new Db;
        $dbAdapter->setData(array('entity_class' => 'stdClass'));
    }

    public function testSearches()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator(
                'search', 'OmekaTest\Api\TestEntity', null, array('data_in'), array('data_out')
            )
        );
        $this->dbAdapter->setData(array('entity_class' => 'OmekaTest\Api\TestEntity'));
        $this->assertEquals(
            array(array('data_out'), array('data_out')),
            $this->dbAdapter->search(array('data_in'))
        );
    }

    public function testCreates()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator(
                'create', null, null, null, null
            )
        );
        $this->dbAdapter->setData(array('entity_class' => 'OmekaTest\Api\TestEntity'));
        $this->assertEquals(array('data_out'), $this->dbAdapter->create(array('data_in')));
    }

    public function testReads()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator(
                'read', 'OmekaTest\Api\TestEntity', 'id', null, array('data_out')
            )
        );
        $this->dbAdapter->setData(array('entity_class' => 'OmekaTest\Api\TestEntity'));
        $this->assertEquals(array('data_out'), $this->dbAdapter->read('id', array()));
    }

    public function testUpdates()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator(
                'update', 'OmekaTest\Api\TestEntity', 'id', array('data_in'), array('data_out')
            )
        );
        $this->dbAdapter->setData(array('entity_class' => 'OmekaTest\Api\TestEntity'));
        $this->assertEquals(array('data_out'), $this->dbAdapter->update('id', array('data_in')));
    }

    public function testDeletes()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator(
                'delete', 'OmekaTest\Api\TestEntity', 'id', null, array('data_out')
            )
        );
        $this->dbAdapter->setData(array('entity_class' => 'OmekaTest\Api\TestEntity'));
        $this->assertEquals(array('data_out'), $this->dbAdapter->delete('id', array()));
    }

    protected function getServiceLocator($operation, $entityClass, $id, $dataIn, $dataOut)
    {
        // The create operation is a special case since it instantiates its own
        // concrete test entity (See TestEntity class below).
        if ('create' !== $operation) {
            // Set the entity to be assigned to the entity repository. 
            $entity = $this->getMock('Omeka\Model\Entity\AbstractEntity');
            if (in_array($operation, array('read', 'update', 'delete'))) {
                $entity->expects($this->once())
                       ->method('toArray')
                       ->will($this->returnValue($dataOut));
            }
            if ('search' === $operation) {
                // Note below that the return value for $entityRepository::search()
                // is set to an array containing exactly two mock entities.
                $entity->expects($this->exactly(2))
                       ->method('toArray')
                       ->will($this->returnValue($dataOut));
            }
            if ('update' === $operation) {
                $entity->expects($this->once())
                       ->method('setData')
                       ->with($this->equalTo($dataIn));
            }

            // Set the entity repository to be assigned to the entity manager.
            $entityRepository = $this->getMockBuilder('Omeka\Model\Repository\AbstractRepository')
                                     ->disableOriginalConstructor()
                                     ->getMock();
            if (in_array($operation, array('read', 'update', 'delete'))) {
                $entityRepository->expects($this->once())
                                 ->method('find')
                                 ->with($this->equalTo($id))
                                 ->will($this->returnValue($entity));
            }
            if ('search' === $operation) {
                $entityRepository->expects($this->once())
                                 ->method('search')
                                 ->with($this->equalTo($dataIn))
                                 ->will($this->returnValue(array($entity, $entity)));
            }
        }

        // Set the entity manager to be set to the service locator.
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                              ->disableOriginalConstructor()
                              ->getMock();
        if (in_array($operation, array('create', 'update', 'delete'))) {
            $entityManager->expects($this->once())
                          ->method('flush');
        }
        if ('create' !== $operation) {
            $entityManager->expects($this->any())
                          ->method('getRepository')
                          ->with($this->equalTo($entityClass))
                          ->will($this->returnValue($entityRepository));
        }
        if ('create' === $operation) {
            $entityManager->expects($this->once())
                          ->method('persist')
                          ->with($this->callback(function($subject) {
                              return $subject instanceof TestEntity;
                          }));
        }
        if ('delete' === $operation) {
            $entityManager->expects($this->once())
                          ->method('remove')
                          ->with($this->equalTo($entity));
        }

        // Set the service locator to be assigned to the database adapter.
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $serviceLocator->expects($this->any())
                       ->method('get')
                       ->with($this->equalTo('EntityManager'))
                       ->will($this->returnValue($entityManager));
        return $serviceLocator;
    }
}

class TestEntity implements EntityInterface
{
    public function setData(array $data)
    {
    }

    public function toArray()
    {
        return array('data_out');
    }
}
