<?php
namespace OmekaTest\Service;

use Omeka\Service\ApiManagerFactory;

class ApiManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesService()
    {
        $factory = new ApiManagerFactory;
        $apiManager = $factory->createService(
            $this->getMockServiceLocator(array('api_manager' => array('resources' => array())))
        );
        $this->assertInstanceOf('Omeka\Api\Manager', $apiManager);
    }

    public function getMockServiceLocator(array $config)
    {
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $serviceLocator->expects($this->once())
                       ->method('get')
                       ->with($this->equalTo('Config'))
                       ->will($this->returnValue($config));
        return $serviceLocator;
    }
}
