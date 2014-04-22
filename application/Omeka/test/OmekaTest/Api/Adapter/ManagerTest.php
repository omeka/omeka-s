<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Api\Adapter\Manager;
use Omeka\Test\TestCase;

class ManagerTest extends TestCase
{
    protected $manager;

    public function setUp()
    {
        $this->manager = new Manager;
    }

    public function testConstructRequiresConfigInterface()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->manager->__construct(new \stdClass);
    }

    public function testInjectsAdapterDependencies()
    {
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $mockServiceManager = $this->getMock('Zend\ServiceManager\AbstractPluginManager');
        $mockServiceManager->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue(
                $this->getMock('Zend\ServiceManager\ServiceLocatorInterface')
            ));
        $this->manager->injectAdapterDependencies($mockAdapter, $mockServiceManager);
    }

    public function testValidatePluginRequiresAdapterInterface()
    {
        $this->setExpectedException('Omeka\Api\Exception\ConfigException');
        $this->manager->validatePlugin(new \stdClass);
    }
}
