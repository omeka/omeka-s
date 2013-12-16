<?php
namespace OmekaTest\Module;

use Omeka\Test\MockBuilder;

class AbstractModuleTest extends \PHPUnit_Framework_TestCase
{
    public function testOnBootstrapSetsParams()
    {
        $mockBuilder = new MockBuilder;

        $module = $this->getMockForAbstractClass('Omeka\Module\AbstractModule');

        $sharedManager = $this->getMock('Zend\EventManager\SharedEventManager');
        $sharedManager->expects($this->once())
            ->method('attachAggregate')
            ->with($this->equalTo($module));

        $viewHelperManager = $this->getMockBuilder('Zend\View\HelperPluginManager')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceManager = $mockBuilder->getServiceManager(
            array('viewhelpermanager' => $viewHelperManager)
        );

        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->once())
            ->method('getSharedManager')
            ->will($this->returnValue($sharedManager));

        $application = $this->getMockBuilder('Zend\Mvc\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $application->expects($this->once())
            ->method('getServiceManager')
            ->will($this->returnValue($serviceManager));
        $application->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnValue($eventManager));

        $event = $this->getMock('Zend\Mvc\MvcEvent');
        $event->expects($this->once())
            ->method('getApplication')
            ->will($this->returnValue($application));

        $module->onBootstrap($event);

        $this->assertSame($sharedManager, $module->getSharedManager());
        $this->assertSame($serviceManager, $module->getServiceLocator());
    }
}
