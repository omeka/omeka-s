<?php
namespace OmekaTest\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Manager;
use Omeka\Api\Request;
use Omeka\Api\RequestAwareInterface;
use Omeka\Api\Response;
use Omeka\Test\MockBuilder;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;
    protected $mockBuilder;
    
    protected $validConfig = array(
        'adapter_class' => 'OmekaTest\Api\TestAdapter',
    );

    public function setUp()
    {
        $this->manager = new Manager;
        $this->mockBuilder = new MockBuilder;
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClassKey()
    {
        $invalidConfig = $this->validConfig;
        unset($invalidConfig['adapter_class']);
        $this->manager->registerResource('foo', $invalidConfig);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClass()
    {
        $invalidConfig = $this->validConfig;
        $invalidConfig['adapter_class'] = 'foo';
        $this->manager->registerResource('foo', $invalidConfig);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClassToImplementAdapterInterface()
    {
        $invalidConfig = $this->validConfig;
        $invalidConfig['adapter_class'] = 'stdClass';
        $this->manager->registerResource('foo', $invalidConfig);
    }

    public function testRegisterResourcesWorks()
    {
        $this->manager->registerResources(array(
            'foo' => $this->validConfig,
            'bar' => $this->validConfig,
        ));
        $this->assertEquals(array(
            'foo' => $this->validConfig,
            'bar' => $this->validConfig,
        ), $this->manager->getResources());
    }

    public function testGetResource()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->assertEquals($this->validConfig, $this->manager->getResource('foo'));
    }

    public function testGetResources()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->manager->registerResource('bar', $this->validConfig);
        $this->assertEquals(array(
            'foo' => $this->validConfig,
            'bar' => $this->validConfig,
        ), $this->manager->getResources());
    }

    public function testResourceIsRegisteredWorks()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->assertTrue($this->manager->resourceIsRegistered('foo'));
        $this->assertFalse($this->manager->resourceIsRegistered('bar'));
    }

    public function testSetsAndGetsServiceLocator()
    {
        $this->manager->setServiceLocator($this->mockBuilder->getServiceManager());
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $this->manager->getServiceLocator()
        );
    }

    public function testExecuteRequiresValidRequestResource()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->exactly(2))
                ->method('getResource')
                ->will($this->returnValue('bar'));
        $this->manager->registerResource('foo', $this->validConfig);
        $this->setServiceLocatorAndEventManager();
        $response = $this->manager->execute($request);
        $this->assertEquals(Response::ERROR_BAD_REQUEST, $response->getStatus());
    }

    public function testExecuteRequiresValidRequestOperation()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue('foo'));
        $request->expects($this->any())
                ->method('getOperation')
                ->will($this->returnValue('bar'));
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $this->manager->registerResource('foo', $this->validConfig);
        $this->setServiceLocatorAndEventManager();
        $response = $this->manager->execute($request, $adapter);
        $this->assertEquals(Response::ERROR_BAD_REQUEST, $response->getStatus());
    }

    public function testExecuteRequiresValidResponse()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue('foo'));
        $request->expects($this->any())
                ->method('getOperation')
                ->will($this->returnValue('delete'));
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $this->manager->registerResource('foo', $this->validConfig);
        $this->setServiceLocatorAndEventManager();
        $response = $this->manager->execute($request, $adapter);
        $this->assertEquals(Response::ERROR_BAD_RESPONSE, $response->getStatus());

    }

    public function testExecuteReturnsExpectedResponseForSearch()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->assertExecuteReturnsExpectedResponse('foo', 'search');
    }

    public function testExecuteReturnsExpectedResponseForCreate()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->assertExecuteReturnsExpectedResponse('foo', 'create');
    }

    public function testExecuteReturnsExpectedResponseForRead()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->assertExecuteReturnsExpectedResponse('foo', 'read');
    }

    public function testExecuteReturnsExpectedResponseForUpdate()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->assertExecuteReturnsExpectedResponse('foo', 'update');
    }

    public function testExecuteReturnsExpectedResponseForDelete()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->assertExecuteReturnsExpectedResponse('foo', 'delete');
    }

    public function testSearchReturnsExpectedResponse()
    {
        $this->setServiceLocatorAndEventManager();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->search('foo', 'data_in');
        $this->assertEquals('search', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testCreateReturnsExpectedResponse()
    {
        $this->setServiceLocatorAndEventManager();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->create('foo', 'data_in');
        $this->assertEquals('create', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testReadReturnsExpectedResponse()
    {
        $this->setServiceLocatorAndEventManager();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->read('foo', 'id', 'data_in');
        $this->assertEquals('read', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('id', $response->getRequest()->getId());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testUpdateReturnsExpectedResponse()
    {
        $this->setServiceLocatorAndEventManager();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->update('foo', 'id', 'data_in');
        $this->assertEquals('update', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('id', $response->getRequest()->getId());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testDeleteReturnsExpectedResponse()
    {
        $this->setServiceLocatorAndEventManager();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->delete('foo', 'id', 'data_in');
        $this->assertEquals('delete', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('id', $response->getRequest()->getId());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    protected function getMockServiceLocatorForInternalErrors()
    {
        $logger = $this->getMock('Zend\Log\Logger');
        $logger->expects($this->once())
            ->method('err');
        return $this->mockBuilder->getServiceManager('Logger', $logger);
    }

    protected function setServiceLocatorAndEventManager()
    {
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->any())
            ->method('setIdentifiers');
        $serviceLocator = $this->mockBuilder->getServiceManager('EventManager', $eventManager);
        $this->manager->setServiceLocator($serviceLocator);
        $this->manager->setEventManager($eventManager);
    }

    protected function assertExecuteReturnsExpectedResponse($resource, $operation)
    {
        $this->setServiceLocatorAndEventManager();
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue($resource));
        $request->expects($this->any())
                ->method('getOperation')
                ->will($this->returnValue($operation));
        $expectedResponse = $this->getMock('Omeka\Api\Response');
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        // Not all operations take an ID argument, but all take a data argument.
        if (in_array($operation, array('search', 'create'))) {
            $adapter->expects($this->once())
                    ->method($operation)
                    ->with($this->equalTo(null))
                    ->will($this->returnValue($expectedResponse));
        } else {
            $adapter->expects($this->once())
                    ->method($operation)
                    ->with($this->equalTo(null), $this->equalTo(null))
                    ->will($this->returnValue($expectedResponse));
        }
        $actualResponse = $this->manager->execute($request, $adapter);
        $this->assertSame($expectedResponse, $actualResponse);
    }
}

class TestAdapter implements
    AdapterInterface,
    ServiceLocatorAwareInterface
{
    protected $request;

    protected $services;

    public function search($data = null)
    {
        return new Response;
    }

    public function create($data = null)
    {
        return new Response;
    }

    public function read($id, $data = null)
    {
        return new Response;
    }

    public function update($id, $data = null)
    {
        return new Response;
    }

    public function delete($id, $data = null)
    {
        return null;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    public function getServiceLocator()
    {
        return $this->services;
    }
}
