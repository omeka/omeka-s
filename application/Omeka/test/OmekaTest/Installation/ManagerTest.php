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

    public function testRegistersAndGetsVars()
    {
        $vars = array('baz' => 'bat');
        $this->manager->registerVars('foo', $vars);
        $this->assertEquals($this->manager->getVars('foo'), $vars);
    }
    
    public function testSetsAndGetsServiceLocator()
    {
        $this->manager->setServiceLocator($this->mockBuilder->getServiceManager());
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $this->manager->getServiceLocator()
        );
    }

    public function testIsInstalled()
    {
        $this->setInstallationState(true);
        $this->assertTrue($this->manager->isInstalled());
    }

    public function testIsNotInstalled()
    {
        $this->setInstallationState(false);
        $this->assertFalse($this->manager->isInstalled());
    }
    
    public function testInstallSuccessfulTask()
    {
        $this->setInstallationState(false);
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
        $this->setInstallationState(false);
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

    protected function setInstallationState($installed)
    {
        $tablePrefix = 'omeka_';
        $tableName = $installed ? Manager::CHECK_TABLE : 'bad_table_name';

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(array('getSchemaManager', 'listTableNames'))
            ->getMock();
        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnSelf());
        $connection->expects($this->once())
            ->method('listTableNames')
            ->will($this->returnValue(array($tablePrefix . $tableName)));
        $serviceManager = $this->mockBuilder->getServiceManager(array(
            'Omeka\Connection' => $connection,
            'ApplicationConfig' => array('connection' => array('table_prefix' => 'omeka_')),
        ));
        $this->manager->setServiceLocator($serviceManager);
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
