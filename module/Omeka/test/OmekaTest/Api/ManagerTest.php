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
        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue('foo'));
        $request->expects($this->any())
                ->method('getOperation')
                ->will($this->returnValue('search'));
        $adapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $adapter->expects($this->once())
                ->method('search')
                ->will($this->returnValue('bar'));
        $response = $this->manager->execute($request, $adapter);
        $this->assertInstanceOf('Omeka\Api\Response', $response);
        $this->assertEquals('bar', $response->getResponse());
        $this->assertInstanceOf('Omeka\Api\Request', $response->getRequest());
        $this->assertEquals('foo', $response->getRequest()->getResource());
        $this->assertEquals('search', $response->getRequest()->getOperation());
    }
}
