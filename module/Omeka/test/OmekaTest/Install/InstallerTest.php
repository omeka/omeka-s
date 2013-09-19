<?php
namespace OmekaTest\Install;

use Omeka\Install\Installer;
use Omeka\Install\TaskAbstract;
use Omeka\Install\TaskResult;
use OmekaTest\Bootstrap;
use Zend\ServiceManager\ServiceLocatorInterface;

class FailPerformTask extends TaskAbstract
{
    public function perform() {
        $this->result->setSuccess(false);
    }
};

class SuccessPerformTask extends TaskAbstract
{
    public function perform() {
        $this->result->setSuccess(true);
    }
};



class InstallTest extends \PHPUnit_Framework_TestCase
{
    protected $tasks;
    
    protected function setUp()
    {
        $this->tasks['successPerformTask'] = new SuccessPerformTask;
        $this->tasks['failPerformTask'] = new FailPerformTask;
        parent::setUp();
    }
    
    /**
     * Make sure that despite successes in tasks, one fail means install fails
     */
    public function testInstallFailsOnTaskFail()
    {
        $installer = new Installer;
        $manager = Bootstrap::getServiceManager();
        $installer->setServiceLocator($manager);
        $installer->addTask($this->tasks['successPerformTask']);
        $installer->addTask($this->tasks['failPerformTask']);
        $installer->addTask($this->tasks['successPerformTask']);
        $status = $installer->install();
        $this->assertFalse($status);
    }
    
    public function testInstallSucceedsOnTasksSucceed()
    {
        $installer = new Installer;
        $manager = Bootstrap::getServiceManager();
        $installer->setServiceLocator($manager);
        $installer->addTask($this->tasks['successPerformTask']);
        $status = $installer->install();
        $this->assertTrue($status);        
    }
    
    /**
     * Make sure tasks listed in application config have classes built for them.
     * Check the inline comments in application.config.php for what the classes should do
     */
    public function testTasksHaveClasses()
    {
        $appConfig = Bootstrap::getApplicationConfig();
        $taskNames = $appConfig['install_tasks'];
        foreach($taskNames as $taskName) {
            $this->assertTrue(class_exists($taskName), "$taskName class does not exist");
        }
    }
}