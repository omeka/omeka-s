<?php
namespace OmekaTest\Api;

use Omeka\Api\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;
    
    protected $config = array(
        'adapter_class' => 'Omeka\Api\Adapter\Db',
        'adapter_data' => array(
            'entity_class' => 'Omeka\Model\Entity\Site',
        ),
        'operations' => array(
            \Omeka\Api\Request::SEARCH,
        ),
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
        $config = $this->config;
        unset($config['adapter_class']);
        $this->manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClass()
    {
        $config = $this->config;
        $config['adapter_class'] = 'Foo';
        $this->manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testAdapterClassImplementsAdapterInterface()
    {
        $config = $this->config;
        $config['adapter_class'] = 'stdClass';
        $this->manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresOperationsKey()
    {
        $config = $this->config;
        unset($config['operations']);
        $this->manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresOperationsArray()
    {
        $config = $this->config;
        $config['operations'] = array();
        $this->manager->registerResource('foo', $config);
    }

    public function testGetResource()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertEquals($this->config, $this->manager->getResource('foo'));
    }

    public function testGetResources()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->manager->registerResource('bar', $this->config);
        $this->assertEquals(array(
            'foo' => $this->config,
            'bar' => $this->config,
        ), $this->manager->getResources());
    }

    public function testIsRegisteredWorks()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertTrue($this->manager->isRegistered('foo'));
        $this->assertFalse($this->manager->isRegistered('bar'));
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

    public function testGetAdapter()
    {
        $this->manager->registerResource('foo', $this->config);
        $mockServiceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->manager->setServiceLocator($mockServiceLocator);
        $adapter = $this->manager->getAdapter('foo');
        $this->assertInstanceOf('Omeka\Api\Adapter\AdapterInterface', $adapter);
        $this->assertEquals($this->config['adapter_data'], $adapter->getData());
        $this->assertSame($mockServiceLocator, $adapter->getServiceLocator());
    }

    /**
     * @expectedException Omeka\Api\Exception\InvalidRequestException
     */
    public function testExecuteRequiresRegisteredOperation()
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue('foo'));
        $request->expects($this->any())
                ->method('getOperation')
                ->will($this->returnValue('bar'));
        $this->manager->execute($request);
    }

    public function testExecuteReturnsExpectedResponseForSearch()
    {
        $response = $this->manager->execute(
            $this->getMockRequestForExecuteTest('search', 'resource', null, 'data_in'),
            $this->getMockAdapterForExecuteTest('search', null, 'data_in', 'data_out')
        );
        $this->assertForExecuteTest($response, 'search', 'resource', null,
            'data_in', 'data_out');
    }

    public function testExecuteReturnsExpectedResponseForCreate()
    {
        $response = $this->manager->execute(
            $this->getMockRequestForExecuteTest('create', 'resource', null, 'data_in'),
            $this->getMockAdapterForExecuteTest('create', null, 'data_in', 'data_out')
        );
        $this->assertForExecuteTest($response, 'create', 'resource', null,
            'data_in', 'data_out');
    }

    public function testExecuteReturnsExpectedResponseForRead()
    {
        $response = $this->manager->execute(
            $this->getMockRequestForExecuteTest('read', 'resource', 'id', 'data_in'),
            $this->getMockAdapterForExecuteTest('read', 'id', 'data_in', 'data_out')
        );
        $this->assertForExecuteTest($response, 'read', 'resource', 'id',
            'data_in', 'data_out');
    }

    public function testExecuteReturnsExpectedResponseForUpdate()
    {
        $response = $this->manager->execute(
            $this->getMockRequestForExecuteTest('update', 'resource', 'id', 'data_in'),
            $this->getMockAdapterForExecuteTest('update', 'id', 'data_in', 'data_out')
        );
        $this->assertForExecuteTest($response, 'update', 'resource', 'id',
            'data_in', 'data_out');
    }

    public function testExecuteReturnsExpectedResponseForDelete()
    {
        $response = $this->manager->execute(
            $this->getMockRequestForExecuteTest('delete', 'resource', 'id', 'data_in'),
            $this->getMockAdapterForExecuteTest('delete', 'id', 'data_in', 'data_out')
        );
        $this->assertForExecuteTest($response, 'delete', 'resource', 'id',
            'data_in', 'data_out');
    }

    protected function getMockRequestForExecuteTest($operation, $resource, $id, $dataIn)
    {
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
                ->method('getOperation')
                ->will($this->returnValue($operation));
        $request->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue($resource));
        if (null !== $id) {
            $request->expects($this->any())
                    ->method('getId')
                    ->will($this->returnValue($id));
        }
        if (null !== $dataIn) {
            $request->expects($this->any())
                    ->method('getData')
                    ->will($this->returnValue($dataIn));
        }
        return $request;
    }

    protected function getMockAdapterForExecuteTest($operation, $id, $dataIn, $dataOut)
    {
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        // Not all operations take an ID argument, but all take a data argument.
        if (null === $id) {
            $adapter->expects($this->once())
                    ->method($operation)
                    ->with($this->equalTo($dataIn))
                    ->will($this->returnValue($dataOut));
        } else {
            $adapter->expects($this->once())
                    ->method($operation)
                    ->with($this->equalTo($id), $this->equalTo($dataIn))
                    ->will($this->returnValue($dataOut));
        }
        return $adapter;
    }

    protected function assertForExecuteTest($response, $operation, $resource,
        $id, $dataIn, $dataOut
    ) {
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertInstanceOf('Omeka\Api\Request', $response->getRequest());
        $this->assertEquals($operation, $response->getRequest()->getOperation());
        $this->assertEquals($resource, $response->getRequest()->getResource());
        // Not all operations take an ID argument.
        if (null !== $id) {
            $this->assertEquals($id, $response->getRequest()->getId());
        }
        // All operations take a data argument.
        $this->assertEquals($dataIn, $response->getRequest()->getData());
        $this->assertEquals($dataOut, $response->getData());
    }
}
