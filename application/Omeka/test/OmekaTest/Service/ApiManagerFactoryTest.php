<?php
namespace OmekaTest\Service;

use Omeka\Service\ApiManagerFactory;

class ApiManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected $validConfig = array(
        'api_resources' => array(),
    );

    public function setUp()
    {
        $this->factory = new ApiManagerFactory;
    }

    public function testCreatesService()
    {
        $apiManager = $this->factory->createService(
            $this->getMockServiceLocator($this->validConfig)
        );
        $this->assertInstanceOf('Omeka\Api\Manager', $apiManager);
    }

    /**
     * @expectedException Omeka\Api\Exception\ConfigException
     */
    public function testRejectsInvalidConfig()
    {
        $invalidConfig = $this->validConfig;
        unset($invalidConfig['api_resources']);
        $apiManager = $this->factory->createService(
            $this->getMockServiceLocator($invalidConfig)
        );
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
