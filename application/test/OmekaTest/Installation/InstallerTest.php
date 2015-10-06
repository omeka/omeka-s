<?php
namespace OmekaTest\Installation;

use Omeka\Installation\Installer;
use Omeka\Installation\Task\TaskInterface;
use Omeka\Test\TestCase;

class InstallerTest extends TestCase
{
    protected $installer;

    public function setUp()
    {
        $this->installer = new Installer;
    }

    public function testRegistersAndGetsVars()
    {
        $vars = ['baz' => 'bat'];
        $this->installer->registerVars('foo', $vars);
        $this->assertEquals($this->installer->getVars('foo'), $vars);
    }
    
    public function testSetsAndGetsServiceLocator()
    {
        $this->installer->setServiceLocator($this->getServiceManager());
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface', 
            $this->installer->getServiceLocator()
        );
    }
    
    public function testInstallSuccessfulTask()
    {
        $this->installer->setServiceLocator($this->getServiceManager(
            [
                'MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'),
                'Omeka\Status' => $this->getMock('Omeka\Mvc\Status')
            ]
        ));
        $this->installer->registerTask('OmekaTest\Installation\SuccessTask');
        $result = $this->installer->install();
        $this->assertTrue($result);
        $this->assertEquals([], $this->installer->getErrors());
    }

    public function testInstallErrorTask()
    {
        $this->installer->setServiceLocator($this->getServiceManager(
            [
                'MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'),
                'Omeka\Status' => $this->getMock('Omeka\Mvc\Status')
            ]
        ));
        $this->installer->registerTask('OmekaTest\Installation\ErrorTask');
        $result = $this->installer->install();
        $this->assertFalse($result);
        $this->assertEquals(['error_message'], $this->installer->getErrors());
    }
}

class SuccessTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
    }
}

class ErrorTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $installer->addError('error_message');
    }
}
