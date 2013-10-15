<?php
namespace OmekaTest\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Manager;
use Omeka\Api\Response;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;
    
    protected $validConfig = array(
        'adapter_class' => 'OmekaTest\Api\TestAdapter',
    );

    public function setUp()
    {
        $this->manager = new Manager;
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
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->manager->setServiceLocator($serviceLocator);
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $this->manager->getServiceLocator()
        );
    }

    public function testGetAdapterWorks()
    {
        $this->manager->registerResource('foo', $this->validConfig);
        $mockServiceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->manager->setServiceLocator($mockServiceLocator);
        $adapter = $this->manager->getAdapter('foo');
        $this->assertInstanceOf('Omeka\Api\Adapter\AdapterInterface', $adapter);
        $this->assertSame($mockServiceLocator, $adapter->getServiceLocator());
    }

    public function testExecuteRequiresValidRequestResource()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->exactly(2))
                ->method('getResource')
                ->will($this->returnValue('bar'));
        $this->manager->registerResource('foo', $this->validConfig);
        $response = $this->manager->execute($request);
        $this->assertEquals(Response::ERROR_INTERNAL, $response->getStatus());
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
        $response = $this->manager->execute($request, $adapter);
        $this->assertEquals(Response::ERROR_INTERNAL, $response->getStatus());
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

    protected function assertExecuteReturnsExpectedResponse($resource, $operation)
    {
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

class TestAdapter implements AdapterInterface, ServiceLocatorAwareInterface
{
    protected $services;

    public function search($data = null)
    {
    }

    public function create($data = null)
    {
    }

    public function read($id, $data = null)
    {
    }

    public function update($id, $data = null)
    {
    }

    public function delete($id, $data = null)
    {
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
