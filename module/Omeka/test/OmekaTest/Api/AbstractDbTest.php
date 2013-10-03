<?php
namespace OmekaTest\Api;

use Omeka\Model\Entity\EntityInterface;
use Omeka\Api\Adapter\AbstractDb;

class DbAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $dbAdapter;

    public function setUp()
    {
        $this->dbAdapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractDb');
    }

    public function testSearches()
    {
        $dataIn = array();
        $responseDataOut = array(array(), array());

        $this->dbAdapter->expects($this->once())
            ->method('findByData')
            ->with($this->equalTo($dataIn))
            ->will($this->returnValue(array(
                new TestEntity,
                new TestEntity,
            )));
        $this->dbAdapter->expects($this->exactly(2))
            ->method('toArray')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue(array()));

        $response = $this->dbAdapter->search($dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals($responseDataOut, $response->getData());
    }

    public function testCreates()
    {
        $dataIn = array();
        $responseDataOut = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('create'));
        $this->dbAdapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('OmekaTest\Api\TestEntity'));
        $this->dbAdapter->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'),
                   $this->equalTo($dataIn));
        $this->dbAdapter->expects($this->once())
            ->method('toArray')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue($responseDataOut));

        $response = $this->dbAdapter->create($dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals($responseDataOut, $response->getData());
    }

    public function testReads()
    {
        $id = 1;
        $dataIn = array();
        $responseDataOut = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('read'));
        //~ $this->dbAdapter->expects($this->once())
            //~ ->method('findEntity')
            //~ ->with($this->equalTo($id))
            //~ ->will($this->returnValue(new TestEntity));
        $this->dbAdapter->expects($this->once())
            ->method('toArray')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue($responseDataOut));

        $response = $this->dbAdapter->read($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals($responseDataOut, $response->getData());
    }

    public function testUpdates()
    {
        $id = 1;
        $dataIn = array();
        $responseDataOut = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('update'));
        $this->dbAdapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('OmekaTest\Api\TestEntity'));
        $this->dbAdapter->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'),
                   $this->equalTo($dataIn));
        $this->dbAdapter->expects($this->once())
            ->method('toArray')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue($responseDataOut));

        $response = $this->dbAdapter->update($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals($responseDataOut, $response->getData());
    }

    public function testDeletes()
    {
        $id = 1;
        $dataIn = array();
        $responseDataOut = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('delete'));
        $this->dbAdapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('OmekaTest\Api\TestEntity'));
        $this->dbAdapter->expects($this->once())
            ->method('toArray')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue($responseDataOut));

        $response = $this->dbAdapter->delete($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals($responseDataOut, $response->getData());
    }

    protected function getServiceLocator($operation)
    {
        // Build the mock entity repository.
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        if (in_array($operation, array('read', 'update', 'delete'))) {
            $repository->expects($this->once())
                ->method('find')
                ->will($this->returnValue(new TestEntity));
        }

        // Build the mock entity manager.
        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        if (in_array($operation, array('read', 'update', 'delete'))) {
            $entityManager->expects($this->once())
                ->method('getRepository')
                ->will($this->returnValue($repository));
        }
        if (in_array($operation, array('create'))) {
            $entityManager->expects($this->once())
                ->method('persist')
                ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'));
        }
        if (in_array($operation, array('delete'))) {
            $entityManager->expects($this->once())
                ->method('remove')
                ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'));
        }
        if (in_array($operation, array('create', 'update', 'delete'))) {
            $entityManager->expects($this->once())
                ->method('flush');
        }

        // Build the mock service locator.
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
}
