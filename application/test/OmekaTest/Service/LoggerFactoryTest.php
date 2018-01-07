<?php
namespace OmekaTest\Service;

use Zend\Log\Logger;
use Omeka\Service\LoggerFactory;
use Omeka\Test\TestCase;

class LoggerFactoryTest extends TestCase
{
    protected $factory;

    protected $validConfig = [
        'logger' => [
            'log' => true,
            'writers' => [
                'stream' => true,
            ],
            'options' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'options' => [
                            'filters' => \Zend\Log\Logger::NOTICE,
                            'stream' => 'php://output',
                        ],
                    ],
                ],
            ],
        ],
    ];

    protected $invalidConfig = [
        'logger' => [
            'log' => true,
            'writers' => [
                'stream' => true,
            ],
            'options' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'options' => [
                            'filters' => \Zend\Log\Logger::NOTICE,
                            'stream' => '/',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function setUp()
    {
        $this->factory = new LoggerFactory;
    }

    public function testCreatesService()
    {
        $factory = $this->factory;
        $logger = $factory(
            $this->getMockServiceLocator($this->validConfig), 'Foo'
        );
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testCreatesServiceWithInvalidPath()
    {
        $factory = $this->factory;
        $this->expectException(\Zend\ServiceManager\Exception\ServiceNotCreatedException::class);
        $logger = $factory(
            $this->getMockServiceLocator($this->invalidConfig), 'Foo'
        );
    }

    protected function getMockServiceLocator(array $config)
    {
        $serviceLocator = $this->createMock('Zend\ServiceManager\ServiceLocatorInterface');
        $serviceLocator->expects($this->once())
            ->method('get')
            ->with($this->equalTo('Config'))
            ->will($this->returnValue($config));
        return $serviceLocator;
    }
}
