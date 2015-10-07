<?php
namespace OmekaTest\Service;

use Omeka\Service\LoggerFactory;
use Omeka\Test\TestCase;

class LoggerFactoryTest extends TestCase
{
    protected $factory;

    protected $validConfig = [
        'logger' => [
            'log' => true,
            'path' => '/',
        ],
    ];

    public function setUp()
    {
        $this->factory = new LoggerFactory;
    }

    public function testCreatesService()
    {
        $logger = $this->factory->createService(
            $this->getMockServiceLocator($this->validConfig)
        );
        $this->assertInstanceOf('Zend\Log\Logger', $logger);
    }

    protected function getMockServiceLocator(array $config)
    {
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $serviceLocator->expects($this->once())
            ->method('get')
            ->with($this->equalTo('Config'))
            ->will($this->returnValue($config));
        return $serviceLocator;
    }
}
