<?php
namespace OmekaTest\Api;

use Omeka\Api\Manager;

class ApiManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $config = array(
        'adapter_class' => 'Omeka\Api\Adapter\Db',
        'adapter_data' => array(
            'entity_class' => 'Omeka\Model\Entity\Site',
        ),
        'operations' => array(
            \Omeka\Api\Request::SEARCH,
        ),
    );
    
    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClassKey()
    {
        $manager = new Manager;
        $config = $this->config;
        unset($config['adapter_class']);
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClass()
    {
        $manager = new Manager;
        $config = $this->config;
        $config['adapter_class'] = 'Foo';
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testAdapterClassImplementsAdapterInterface()
    {
        $manager = new Manager;
        $config = $this->config;
        $config['adapter_class'] = 'stdClass';
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresOperationsKey()
    {
        $manager = new Manager;
        $config = $this->config;
        unset($config['operations']);
        $manager->registerResource('foo', $config);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresOperationsArray()
    {
        $manager = new Manager;
        $config = $this->config;
        $config['operations'] = array();
        $manager->registerResource('foo', $config);
    }

    public function testGetResource()
    {
        $manager = new Manager;
        $manager->registerResource('foo', $this->config);
        $this->assertEquals($this->config, $manager->getResource('foo'));
    }

    public function testGetResources()
    {
        $manager = new Manager;
        $manager->registerResource('foo', $this->config);
        $manager->registerResource('bar', $this->config);
        $this->assertEquals(array(
            'foo' => $this->config,
            'bar' => $this->config,
        ), $manager->getResources());
    }

    public function testIsRegisteredWorks()
    {
        $manager = new Manager;
        $manager->registerResource('foo', $this->config);
        $this->assertTrue($manager->isRegistered('foo'));
        $this->assertFalse($manager->isRegistered('bar'));
    }

    public function testSetsAndGetsServiceLocator()
    {
        $manager = new Manager;
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $manager->setServiceLocator($serviceLocator);
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $manager->getServiceLocator()
        );
    }

    public function testGetAdapter()
    {
        $manager = new Manager;
        $manager->registerResource('foo', $this->config);
        $mockServiceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $manager->setServiceLocator($mockServiceLocator);
        $adapter = $manager->getAdapter('foo');
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
        $request->setResource('foo');
        $request->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue('foo'));
        $request->expects($this->any())
                ->method('getOperation')
                ->will($this->returnValue('bar'));
        $manager = new Manager;
        $manager->execute($request);
    }
}
