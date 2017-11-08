<?php
namespace OmekaTest\Service;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Request;
use Omeka\Api\ResourceInterface as ApiResourceInterface;
use Omeka\Service\ApiAdapterManagerFactory;
use Omeka\Test\TestCase;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiAdapterManagerFactoryTest extends TestCase
{
    public function testCreatesService()
    {
        $config = [
            'api_adapters' => [
                'invokables' => [
                    'test_adapter' => 'OmekaTest\Service\TestAdapter',
                ],
            ],
        ];

        $serviceManager = $this->getServiceManager([
            'Config' => $config,
            'EventManager' => $this->getMockForAbstractClass('Zend\EventManager\EventManagerInterface'),
        ]);
        $factory = new ApiAdapterManagerFactory;
        $service = $factory($serviceManager, 'Foo');

        $this->assertInstanceOf('Omeka\Api\Adapter\Manager', $service);

        // The adapter manager injects the service manager into the adapter.
        $this->assertInstanceOf(
            'OmekaTest\Service\TestAdapter',
            $service->get('test_adapter')
        );
    }
}

class TestAdapter implements AdapterInterface
{
    public function getResourceName()
    {
    }
    public function getRepresentationClass()
    {
    }
    public function search(Request $request)
    {
    }
    public function create(Request $request)
    {
    }
    public function batchCreate(Request $request)
    {
    }
    public function read(Request $request)
    {
    }
    public function update(Request $request)
    {
    }
    public function batchUpdate(Request $request)
    {
    }
    public function preprocessBatchUpdate(array $data, Request $request)
    {
    }
    public function delete(Request $request)
    {
    }
    public function batchDelete(Request $request)
    {
    }
    public function getRepresentation(ApiResourceInterface $data = null)
    {
    }
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
    }
    public function getServiceLocator()
    {
    }
    public function setEventManager(EventManagerInterface $events)
    {
    }
    public function getEventManager()
    {
    }
    public function getResourceId()
    {
    }
}
