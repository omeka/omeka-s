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
            \Omeka\Api\Request::CREATE,
            \Omeka\Api\Request::READ,
            \Omeka\Api\Request::UPDATE,
            \Omeka\Api\Request::DELETE,
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

    public function testResourceIsRegisteredWorks()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertTrue($this->manager->resourceIsRegistered('foo'));
        $this->assertFalse($this->manager->resourceIsRegistered('bar'));
    }

    public function testResourceAllowsOperationWorks()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertTrue($this->manager->resourceAllowsOperation(
            'foo', \Omeka\Api\Request::SEARCH
        ));
        $this->assertFalse($this->manager->resourceAllowsOperation(
            'foo', 'bar'
        ));
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

    public function _testExecuteReturnsExpectedResponseForSearch()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertExecuteReturnsExpectedResponse('foo', 'search');
    }

    public function testExecuteReturnsExpectedResponseForCreate()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertExecuteReturnsExpectedResponse('foo', 'create');
    }

    public function testExecuteReturnsExpectedResponseForRead()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertExecuteReturnsExpectedResponse('foo', 'read');
    }

    public function testExecuteReturnsExpectedResponseForUpdate()
    {
        $this->manager->registerResource('foo', $this->config);
        $this->assertExecuteReturnsExpectedResponse('foo', 'update');
    }

    public function testExecuteReturnsExpectedResponseForDelete()
    {
        $this->manager->registerResource('foo', $this->config);
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
