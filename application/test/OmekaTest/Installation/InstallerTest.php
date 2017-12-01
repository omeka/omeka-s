<?php
namespace OmekaTest\Installation;

use Omeka\Installation\Installer;
use Omeka\Installation\Task\TaskInterface;
use Omeka\Test\TestCase;

class InstallerTest extends TestCase
{
    public function testRegistersAndGetsVars()
    {
        $installer = new Installer($this->getServiceManager());
        $vars = ['baz' => 'bat'];
        $installer->registerVars('foo', $vars);
        $this->assertEquals($installer->getVars('foo'), $vars);
    }

    public function testSetsAndGetsServiceLocator()
    {
        $installer = new Installer($this->getServiceManager());
        $this->assertInstanceOf(
            'Zend\ServiceManager\ServiceLocatorInterface',
            $installer->getServiceLocator()
        );
    }

    public function testInstallSuccessfulTask()
    {
        $installer = new Installer($this->getConfiguredServiceManager());
        $installer->registerTask('OmekaTest\Installation\SuccessTask');
        $result = $installer->install();
        $this->assertTrue($result);
        $this->assertEquals([], $installer->getErrors());
    }

    public function testInstallErrorTask()
    {
        $installer = new Installer($this->getConfiguredServiceManager());
        $installer->registerTask('OmekaTest\Installation\ErrorTask');
        $result = $installer->install();
        $this->assertFalse($result);
        $this->assertEquals(['error_message'], $installer->getErrors());
    }

    public function testPreInstallSuccessfulTask()
    {
        $installer = new Installer($this->getConfiguredServiceManager());
        $installer->registerPreTask('OmekaTest\Installation\SuccessTask');
        $result = $installer->preInstall();
        $this->assertTrue($result);
        $this->assertEquals([], $installer->getErrors());
    }

    public function testPreInstallErrorTask()
    {
        $installer = new Installer($this->getConfiguredServiceManager());
        $installer->registerPreTask('OmekaTest\Installation\ErrorTask');
        $result = $installer->preInstall();
        $this->assertFalse($result);
        $this->assertEquals(['error_message'], $installer->getErrors());
    }

    public function testPreInstallErrorInstallSuccessTask()
    {
        $installer = new Installer($this->getConfiguredServiceManager());
        $installer->registerPreTask('OmekaTest\Installation\ErrorTask');
        $installer->registerTask('OmekaTest\Installation\SuccessTask');
        $result = $installer->preInstall();
        $this->assertFalse($result);
        $this->assertEquals(['error_message'], $installer->getErrors());
    }

    public function getConfiguredServiceManager()
    {
        $status = $this->getMockBuilder('Omeka\Mvc\Status')->disableOriginalConstructor()->getMock();
        $translator = $this->createMock('Zend\I18n\Translator\Translator');
        return $this->getServiceManager([
            'MvcTranslator' => $translator,
            'Omeka\Status' => $status,
        ]);
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
