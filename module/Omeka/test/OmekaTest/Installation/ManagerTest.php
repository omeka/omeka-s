<?php
namespace OmekaTest\Installation;

use Omeka\Installation\Manager;
use Omeka\Installation\Result;
use Omeka\Installation\Task\AbstractTask;
use Omeka\Test\MockBuilder;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;

    public function setUp()
    {
        $this->manager = new Manager;
        $this->mockBuilder = new MockBuilder;
    }

    /**
     * @expectedException Omeka\Installation\Exception\ConfigException
     */
    public function testRegistrationRequiresValidClass()
    {
        $this->manager->registerTask('Foo\Bar');
    }

    /**
     * @expectedException Omeka\Installation\Exception\ConfigException
     */
    public function testRegistrationRequiresClassToImplementTaskInterface()
    {
        $this->manager->registerTask('stdClass');
    }

    public function testRegisterTasksWorks()
    {
        $tasks = array(
            'OmekaTest\Installation\SuccessTask',
            'OmekaTest\Installation\ErrorTask',
        );
        $this->manager->registerTasks($tasks);
        $this->assertEquals($tasks, $this->manager->getTasks());
    }
    
    public function testSetsAndGetsServiceLocator()
    {
        $this->manager->setServiceLocator($this->mockBuilder->getServiceManager());
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $this->manager->getServiceLocator()
        );
    }
    
    public function testInstallSuccessfulTask()
    {
        $this->manager->setServiceLocator($this->mockBuilder->getServiceManager());
        $this->manager->registerTask('OmekaTest\Installation\SuccessTask');
        $result = $this->manager->install();
        $this->assertInstanceOf('Omeka\Installation\Result', $result);
        $this->assertFalse($result->isError());
        $this->assertEquals(array(
            'OmekaTest\Installation\SuccessTask' => array(
                'task_name' => 'success_task',
                'info' => array('info_message', 'time: 0.00'),
                'warning' => array('warning_message'),
            )
        ), $result->getMessages());
    }

    public function testInstallErrorTask()
    {
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->manager->setServiceLocator($serviceLocator);
        $this->manager->registerTask('OmekaTest\Installation\ErrorTask');
        $result = $this->manager->install();
        $this->assertInstanceOf('Omeka\Installation\Result', $result);
        $this->assertTrue($result->isError());
        $this->assertEquals(array(
            'OmekaTest\Installation\ErrorTask' => array(
                'task_name' => 'error_task',
                'info' => array('info_message', 'time: 0.00'),
                'warning' => array('warning_message'),
                'error' => array('error_message'),
            )
        ), $result->getMessages());
    }
}

class SuccessTask extends AbstractTask
{
    public function perform()
    {
        $this->addInfo('info_message');
        $this->addWarning('warning_message');
    }

    public function getName()
    {
        return 'success_task';
    }
}

class ErrorTask extends AbstractTask
{
    public function perform()
    {
        $this->addInfo('info_message');
        $this->addWarning('warning_message');
        $this->addError('error_message');
    }

    public function getName()
    {
        return 'error_task';
    }
}
