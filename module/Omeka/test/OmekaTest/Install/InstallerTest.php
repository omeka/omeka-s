<?php
namespace OmekaTest\Install;

use Omeka\Install\Installer;
use Omeka\Install\Task\AbstractTask;
use Omeka\Install\Task\TaskResult;
use OmekaTest\Bootstrap;
use Zend\ServiceManager\ServiceLocatorInterface;

/*
 * Create a fake task that fails
 */

class FailPerformTask extends AbstractTask
{
    protected $taskName = "Test Task Failure";
    
    public function perform() {
        $this->result->setSuccess(false);
    }
};

/*
 * Create a fake task that succeeds
 */

class SuccessPerformTask extends AbstractTask
{
    protected $taskName = "Test Task Success";
    
    public function perform() {
        $this->result->setSuccess(true);
    }
};

class InstallTest extends \PHPUnit_Framework_TestCase
{
    protected $tasks;
    protected $installer;
    
    protected function setUp()
    {
        $this->tasks['successPerformTask'] = new SuccessPerformTask;
        $this->tasks['failPerformTask'] = new FailPerformTask;
        $this->installer = Bootstrap::getServiceManager()->get('Installer');
        parent::setUp();
    }
    
    /**
     * Make sure that despite successes in tasks, one fail means install fails
     */
    public function testInstallFailsOnTaskFail()
    {
        $this->installer->emptyTasks();
        $this->installer->addTask($this->tasks['successPerformTask']);
        $this->installer->addTask($this->tasks['failPerformTask']);
        $this->installer->addTask($this->tasks['successPerformTask']);
        $status = $this->installer->install();
        $this->assertFalse($status);
    }
    
    public function testInstallSucceedsOnTasksSucceed()
    {
        $this->installer->emptyTasks();
        $this->installer->addTask($this->tasks['successPerformTask']);
        $status = $this->installer->install();
        $this->assertTrue($status);        
    }
    
    /**
     * Make sure tasks listed in application config have classes built for them.
     * Check the inline comments in application.config.php for what the classes should do
     */
    public function testTasksHaveClasses()
    {
        $appConfig = Bootstrap::getServiceManager()->get('Config');
        $taskNames = $appConfig['install']['tasks'];
        foreach($taskNames as $taskName) {
            $this->assertTrue(class_exists($taskName), "$taskName class does not exist");
        }
    }
}