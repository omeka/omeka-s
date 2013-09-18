<?php
namespace OmekaTest\Api;

use Omeka\Service\ApiManagerFactory;

class ManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesService()
    {
        $config = array('api_manager' => array('resources' => array()));
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $serviceLocator->expects($this->once())
                       ->method('get')
                       ->with($this->equalTo('Config'))
                       ->will($this->returnValue($config));
        $factory = new ApiManagerFactory;
        $apiManager = $factory->createService($serviceLocator);
        $this->assertInstanceOf('Omeka\Api\Manager', $apiManager);
    }
}
