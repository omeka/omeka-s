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
    public function testSetDataRequiresEntityClass()
    {
        $dbAdapter = new Db;
        $dbAdapter->setData(array());
    }

    public function testSearches()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator('search', 'entity_class', null, 'data_in', 'data_out')
        );
        $this->dbAdapter->setData(array('entity_class' => 'entity_class'));
        $this->assertEquals(
            array('data_out', 'data_out'),
            $this->dbAdapter->search('data_in')
        );
    }

    public function testCreates()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator('create', null, null, null, null)
        );
        $this->dbAdapter->setData(array('entity_class' => 'OmekaTest\Api\TestEntity'));
        $this->assertEquals('data_out', $this->dbAdapter->create('data_in'));
    }

    public function testReads()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator('read', 'OmekaTest\Api\TestEntity', 'id', 'data_in', 'data_out')
        );
        $this->dbAdapter->setData(array('entity_class' => 'OmekaTest\Api\TestEntity'));
        $this->assertEquals('data_out', $this->dbAdapter->read('id', 'data_in'));
    }

    public function testUpdates()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator('update', 'entity_class', 'id', 'data_in', 'data_out')
        );
        $this->dbAdapter->setData(array('entity_class' => 'entity_class'));
        $this->assertEquals('data_out', $this->dbAdapter->update('id', 'data_in'));
    }

    public function testDeletes()
    {
        $this->dbAdapter->setServiceLocator(
            $this->getServiceLocator('delete', 'entity_class', 'id', 'data_in', 'data_out')
        );
        $this->dbAdapter->setData(array('entity_class' => 'entity_class'));
        $this->assertEquals('data_out', $this->dbAdapter->delete('id', 'data_in'));
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
    public function setData($data)
    {
    }

    public function toArray()
    {
        return 'data_out';
    }
}
