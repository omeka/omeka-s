<?php
namespace OmekaTest\Service;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Request;
use Omeka\Service\ApiAdapterManagerFactory;
use Omeka\Test\TestCase;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiAdapterManagerFactoryTest extends TestCase
{
    public function testCreatesService()
    {
        $config = array(
            'api_adapters' => array(
                'invokables' => array(
                    'test_adapter' => 'OmekaTest\Service\TestAdapter',
                ),
            ),
        );

        $serviceManager = $this->getServiceManager(array(
            'Config' => $config,
        ));
        $factory = new ApiAdapterManagerFactory;
        $service = $factory->createService($serviceManager);

        $this->assertInstanceOf('Omeka\Api\Adapter\Manager', $service);

        // The adapter manager injects the service manager into the adapter.
        $service->setServiceLocator($serviceManager);
        $this->assertInstanceOf(
            'OmekaTest\Service\TestAdapter',
            $service->get('test_adapter')
        );
    }
}

class TestAdapter implements AdapterInterface
{
    public function search(Request $request){}
    public function create(Request $request){}
    public function batchCreate(Request $request){}
    public function read(Request $request){}
    public function update(Request $request){}
    public function delete(Request $request){}
    public function getApiUrl($resource){}
    public function getWebUrl($resource){}
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator){}
    public function getServiceLocator(){}
    public function setEventManager(EventManagerInterface $events){}
    public function getEventManager(){}
    public function getResourceId(){}
}
