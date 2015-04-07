<?php
namespace OmekaTest\Installation;

use Omeka\Installation\Manager;
use Omeka\Installation\Task\TaskInterface;
use Omeka\Test\TestCase;

class ManagerTest extends TestCase
{
    protected $manager;

    public function setUp()
    {
        $this->manager = new Manager;
    }

    /**
     * @expectedException Omeka\Service\Exception\ConfigException
     */
    public function testRegistrationRequiresValidClass()
    {
        $this->manager->setServiceLocator($this->getServiceManager(
            array('MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'))
        ));
        $this->manager->registerTask('Foo\Bar');
    }

    /**
     * @expectedException Omeka\Service\Exception\ConfigException
     */
    public function testRegistrationRequiresClassToImplementTaskInterface()
    {
        $this->manager->setServiceLocator($this->getServiceManager(
            array('MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'))
        ));
        $this->manager->registerTask('stdClass');
    }

    public function testRegisterTasksWorks()
    {
        $this->manager->setServiceLocator($this->getServiceManager(
            array('MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'))
        ));
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
        $this->manager->setServiceLocator($this->getServiceManager());
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $this->manager->getServiceLocator()
        );
    }
    
    public function testInstallSuccessfulTask()
    {
        $this->manager->setServiceLocator($this->getServiceManager(
            array(
                'MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'),
                'Omeka\Status' => $this->getMock('Omeka\Mvc\Status')
            )
        ));
        $this->manager->registerTask('OmekaTest\Installation\SuccessTask');
        $result = $this->manager->install();
        $this->assertTrue($result);
        $this->assertEquals(array(), $this->manager->getErrors());
    }

    public function testInstallErrorTask()
    {
        $this->manager->setServiceLocator($this->getServiceManager(
            array(
                'MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'),
                'Omeka\Status' => $this->getMock('Omeka\Mvc\Status')
            )
        ));
        $this->manager->registerTask('OmekaTest\Installation\ErrorTask');
        $result = $this->manager->install();
        $this->assertFalse($result);
        $this->assertEquals(array('error_message'), $this->manager->getErrors());
    }
}

class SuccessTask implements TaskInterface
{
    public function perform(Manager $manager)
    {
    }
}

class ErrorTask implements TaskInterface
{
    public function perform(Manager $manager)
    {
        $manager->addError('error_message');
    }
}
