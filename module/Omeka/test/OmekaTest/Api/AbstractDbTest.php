<?php
namespace OmekaTest\Api;

use Doctrine\ORM\EntityManager;
use Omeka\Api\Adapter\AbstractDb;
use Omeka\StdLib\ErrorStore;
use Omeka\Model\Entity\AbstractEntity;
use Omeka\Model\Exception as ModelException;

class DbAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $dbAdapter;

    public function setUp()
    {
        $this->dbAdapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\AbstractDb'
        );
    }

    public function testSearches()
    {
        $dataIn = array();
        $responseDataOut = array(
            array('foo' => 'bar'),
            array('foo' => 'bar'),
        );

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
            ->will($this->returnValue(array('foo' => 'bar')));

        $response = $this->dbAdapter->search($dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals($responseDataOut, $response->getData());
    }

    public function testCreates()
    {
        $dataIn = array();
        $responseDataOut = array('foo' => 'bar');

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
        $this->assertEquals('success', $response->getStatus());
        $this->assertNull($response->getErrors());
    }

    public function testCreateHandlesValidationError()
    {
        $dataIn = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('create', null, true));
        $this->dbAdapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('OmekaTest\Api\TestEntity'));
        $this->dbAdapter->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'),
                   $this->equalTo($dataIn));

        $response = $this->dbAdapter->create($dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertNull(null, $response->getData());
        $this->assertEquals('error_validation', $response->getStatus());
        $this->assertEquals(
            array('foo' => array('foo_message')),
            $response->getErrors()
        );
    }

    public function testReads()
    {
        $id = 1;
        $dataIn = array();
        $responseDataOut = array('foo' => 'bar');

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('read', $id));
        $this->dbAdapter->expects($this->once())
            ->method('toArray')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'))
            ->will($this->returnValue($responseDataOut));

        $response = $this->dbAdapter->read($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals($responseDataOut, $response->getData());
        $this->assertEquals('success', $response->getStatus());
        $this->assertNull($response->getErrors());
    }

    public function testReadHandlesNotFoundError()
    {
        $id = 1;
        $dataIn = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator(
            'read', $id, false, true
        ));

        $response = $this->dbAdapter->read($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertNull(null, $response->getData());
        $this->assertEquals('error_not_found', $response->getStatus());
        $this->assertEquals(
            array('error_not_found' => array('error_not_found_message')),
            $response->getErrors()
        );
    }

    public function testUpdates()
    {
        $id = 1;
        $dataIn = array();
        $responseDataOut = array('foo' => 'bar');

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('update', $id));
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
        $this->assertEquals('success', $response->getStatus());
        $this->assertNull($response->getErrors());
    }

    public function testUpdateHandlesNotFoundErrors()
    {
        $id = 1;
        $dataIn = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator(
            'update', $id, false, true));
        $this->dbAdapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('OmekaTest\Api\TestEntity'));

        $response = $this->dbAdapter->update($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertNull($response->getData());
        $this->assertEquals('error_not_found', $response->getStatus());
        $this->assertEquals(
            array('error_not_found' => array('error_not_found_message')),
            $response->getErrors()
        );
    }

    public function testUpdateHandlesValidationErrors()
    {
        $id = 1;
        $dataIn = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator(
            'update', $id, true));
        $this->dbAdapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('OmekaTest\Api\TestEntity'));
        $this->dbAdapter->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'),
                   $this->equalTo($dataIn));

        $response = $this->dbAdapter->update($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertNull($response->getData());
        $this->assertEquals('error_validation', $response->getStatus());
        $this->assertEquals(
            array('foo' => array('foo_message')),
            $response->getErrors()
        );
    }

    public function testDeletes()
    {
        $id = 1;
        $dataIn = array();
        $responseDataOut = array('foo' => 'bar');

        $this->dbAdapter->setServiceLocator($this->getServiceLocator('delete', $id));
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
        $this->assertEquals('success', $response->getStatus());
        $this->assertNull($response->getErrors());
    }

    public function testDeleteHandlesNotFoundErrors()
    {
        $id = 1;
        $dataIn = array();

        $this->dbAdapter->setServiceLocator($this->getServiceLocator(
            'delete', $id, false, true));
        $this->dbAdapter->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('OmekaTest\Api\TestEntity'));

        $response = $this->dbAdapter->delete($id, $dataIn);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertNull($response->getData());
        $this->assertEquals('error_not_found', $response->getStatus());
        $this->assertEquals(
            array('error_not_found' => array('error_not_found_message')),
            $response->getErrors()
        );
    }

    protected function getServiceLocator($operation, $id = null,
        $throwValidationException = false, $throwNotFoundException = false
    ) {
        // Build the mock entity repository.
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        if (in_array($operation, array('read', 'update', 'delete'))) {
            if ($throwNotFoundException) {
                $repository->expects($this->once())
                    ->method('find')
                    ->with($this->equalTo($id))
                    ->will($this->throwException(
                        new ModelException\EntityNotFoundException('error_not_found_message')
                    ));
            } else {
                $repository->expects($this->once())
                    ->method('find')
                    ->with($this->equalTo($id))
                    ->will($this->returnValue(new TestEntity));
            }
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
        // When expecting an error_not_found exception, do not expect a call to
        // flush() or remove(). The exception is thrown before they are called.
        if (!$throwNotFoundException) {
            if (in_array($operation, array('delete'))) {
                $entityManager->expects($this->once())
                    ->method('remove')
                    ->with($this->isInstanceOf('Omeka\Model\Entity\EntityInterface'));
            }

            if (in_array($operation, array('create', 'update', 'delete'))) {
                if ($throwValidationException) {
                    $exception = $this->getMock('Omeka\Model\Exception\EntityValidationException');
                    $errorStore = $this->getMock('Omeka\StdLib\ErrorStore');
                    $errorStore->expects($this->once())
                             ->method('getErrors')
                             ->will($this->returnValue(array('foo' => array('foo_message'))));
                    $exception->expects($this->once())
                              ->method('getErrorStore')
                              ->will($this->returnValue($errorStore));
                    $entityManager->expects($this->once())
                        ->method('flush')
                        ->will($this->throwException($exception));
                } else {
                    $entityManager->expects($this->once())
                        ->method('flush');
                }
            }
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

class TestEntity extends AbstractEntity
{
    public function validate(ErrorStore $errorStore, $isPersistent,
        EntityManager $entityManager
    ) {
    }
}
