<?php
namespace OmekaTest\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Manager;
use Omeka\Api\Request;
use Omeka\Api\RequestAwareInterface;
use Omeka\Api\Response;
use Omeka\Test\MockBuilder;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
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

    public function testSetsAndGetsEventManager()
    {
        $this->manager->setEventManager($this->getMock('Zend\EventManager\EventManager'));
        $this->assertInstanceOf(
            'Zend\EventManager\EventManager', 
            $this->manager->getEventManager()
        );
    }

    public function testExecuteRequiresValidRequestResource()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->exactly(2))
                ->method('getResource')
                ->will($this->returnValue('bar'));
        $this->manager->registerResource('foo', $this->validConfig);
        $this->setServiceLocator();
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
        $this->setServiceLocator();
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
        $this->setServiceLocator();
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

    public function testBatchCreateRequiresValidRequestContent()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->setServiceLocator();
        // Nopte that the request has no content. An array should be expected.
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
            ->method('getResource')
            ->will($this->returnValue('foo'));
        $request->expects($this->any())
            ->method('getOperation')
            ->will($this->returnValue('batch_create'));
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $response = $this->manager->execute($request, $adapter);
        $this->assertTrue($response->isError());
        $this->assertEquals(Response::ERROR_BAD_REQUEST, $response->getStatus());
    }

    public function testExecuteReturnsExpectedResponseForBatchCreate()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $this->setServiceLocator();
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
            ->method('getResource')
            ->will($this->returnValue('foo'));
        $request->expects($this->any())
            ->method('getOperation')
            ->will($this->returnValue('batch_create'));
        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(array()));
        $expectedResponse = $this->getMock('Omeka\Api\Response');
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $adapter->expects($this->once())
                ->method('batchCreate')
                ->will($this->returnValue($expectedResponse));
        $actualResponse = $this->manager->execute($request, $adapter);
        $this->assertSame($expectedResponse, $actualResponse);
    }

    public function testSearchReturnsExpectedResponse()
    {
        $this->setServiceLocator();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->search('foo', 'data_in');
        $this->assertEquals('search', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testCreateReturnsExpectedResponse()
    {
        $this->setServiceLocator();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->create('foo', 'data_in');
        $this->assertEquals('create', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testBatchCreateReturnsExpectedResponse()
    {
        $this->setServiceLocator();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->batchCreate('foo', array('data_in'));
        $this->assertEquals('batch_create', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals(array('data_in'), $response->getRequest()->getContent());
    }

    public function testReadReturnsExpectedResponse()
    {
        $this->setServiceLocator();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->read('foo', 'id', 'data_in');
        $this->assertEquals('read', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('id', $response->getRequest()->getId());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testUpdateReturnsExpectedResponse()
    {
        $this->setServiceLocator();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->update('foo', 'id', 'data_in');
        $this->assertEquals('update', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('id', $response->getRequest()->getId());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    public function testDeleteReturnsExpectedResponse()
    {
        $this->setServiceLocator();
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->delete('foo', 'id', 'data_in');
        $this->assertEquals('delete', $response->getRequest()->getOperation());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('id', $response->getRequest()->getId());
        $this->assertEquals('data_in', $response->getRequest()->getContent());
    }

    protected function setServiceLocator()
    {
        $logger = $this->getMock('Zend\Log\Logger');
        $logger->expects($this->any())->method('err');
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->any())->method('setIdentifiers');
        $serviceLocator = $this->mockBuilder->getServiceManager(
            array('EventManager' => $eventManager, 'Logger' => $logger)
        );
        $this->manager->setServiceLocator($serviceLocator);
        $this->manager->setEventManager($eventManager);
    }

    protected function assertExecuteReturnsExpectedResponse($resource, $operation)
    {
        $this->setServiceLocator();
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
    ServiceLocatorAwareInterface,
    EventManagerAwareInterface
{
    protected $request;

    protected $services;

    protected $events;

    public function search($data = null)
    {
        return new Response;
    }

    public function create($data = null)
    {
        return new Response;
    }

    public function batchCreate($data = null)
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
        return Response;
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

    public function setEventManager(EventManagerInterface $events)
    {
        $this->events = $events;
    }

    public function getEventManager()
    {
        return $this->events;
    }
}
