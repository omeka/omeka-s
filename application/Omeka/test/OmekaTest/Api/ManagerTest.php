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
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;
    protected $mockBuilder;
    
    protected $validConfig = array(
        'foo' => 'OmekaTest\Api\TestAdapter',
        'bar' => 'OmekaTest\Api\TestAdapter',
    );

    public function setUp()
    {
        $this->manager = new Manager;
        $this->mockBuilder = new MockBuilder;
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClass()
    {
        $this->manager->registerResource('foo', null);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRegistrationRequiresAdapterClassToImplementAdapterInterface()
    {
        $this->manager->registerResource('foo', 'stdClass');
    }

    public function testRegisterResourcesWorks()
    {
        $this->manager->registerResources($this->validConfig);
        $this->assertEquals($this->validConfig, $this->manager->getResources());
    }

    public function testGetResource()
    {
        $this->manager->registerResource('foo', $this->validConfig['foo']);
        $this->assertEquals($this->validConfig['foo'], $this->manager->getAdapterClass('foo'));
    }

    public function testGetResources()
    {
        $this->manager->registerResource('foo', $this->validConfig['foo']);
        $this->manager->registerResource('bar', $this->validConfig['bar']);
        $this->assertEquals($this->validConfig, $this->manager->getResources());
    }

    public function testResourceIsRegisteredWorks()
    {
        $this->manager->registerResource('foo', $this->validConfig['foo']);
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

    public function testExecuteRequiresValidResource()
    {
        $this->setServiceLocator();
        $request = $this->getMock('Omeka\Api\Request');
        $response = $this->manager->execute($request);
        $this->assertEquals($response->getStatus(), Response::ERROR_BAD_REQUEST);
    }

    public function testExecuteRequiresValidOperation()
    {
        $this->setServiceLocator();
        $this->manager->registerResources($this->validConfig);

        $request = $this->getMock('Omeka\Api\Request');
        $request->expects($this->any())
            ->method('getResource')
            ->will($this->returnValue('foo'));
        $request->expects($this->any())
            ->method('getOperation')
            ->will($this->returnValue('invalid_operation'));

        $response = $this->manager->execute($request);
        $this->assertEquals($response->getStatus(), Response::ERROR_BAD_REQUEST);
    }

    public function testSearch()
    {
        $this->setServiceLocator();
        $this->manager->registerResources($this->validConfig);
        $response = $this->manager->search('foo');
        $this->assertEquals($response->getStatus(), Response::SUCCESS);
    }

    public function testCreate()
    {
        $this->setServiceLocator();
        $this->manager->registerResources($this->validConfig);
        $response = $this->manager->create('foo');
        $this->assertEquals($response->getStatus(), Response::SUCCESS);
    }

    public function testRead()
    {
        $this->setServiceLocator();
        $this->manager->registerResources($this->validConfig);
        $response = $this->manager->read('foo', 1);
        $this->assertEquals($response->getStatus(), Response::SUCCESS);
    }

    public function testUpdate()
    {
        $this->setServiceLocator();
        $this->manager->registerResources($this->validConfig);
        $response = $this->manager->update('foo', 1);
        $this->assertEquals($response->getStatus(), Response::SUCCESS);
    }

    public function testDelete()
    {
        $this->setServiceLocator();
        $this->manager->registerResources($this->validConfig);
        $response = $this->manager->delete('foo', 1);
        $this->assertEquals($response->getStatus(), Response::SUCCESS);
    }

    protected function setServiceLocator()
    {
        $acl = $this->getMock('Omeka\Permissions\Acl');
        $acl->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));

        $serviceManager = $this->mockBuilder->getServiceManager(array(
            'Omeka\Logger' => $this->getMock('Zend\Log\Logger'),
            'EventManager' => $this->getMock('Zend\EventManager\EventManager'),
            'Omeka\Acl' => $acl,
        ));
        $this->manager->setServiceLocator($serviceManager);
    }
}

class TestAdapter implements AdapterInterface
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
        return new Response;
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
        $events->setIdentifiers(get_called_class());
        $this->events = $events;
    }

    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager($this->getServiceLocator()->get('EventManager'));
        }
        return $this->events;
    }

    public function getResourceId()
    {
        return get_called_class();
    }
}
