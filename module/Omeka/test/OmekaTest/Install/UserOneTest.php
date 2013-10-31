<?php
namespace OmekaTest\Install;

use Omeka\Install\Installer;
use Omeka\Install\Task\AbstractTask;
use Omeka\Install\Task\TaskResult;
use Omeka\Install\Task\UserOne;
use OmekaTest\Bootstrap;

class UserOneTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Bootstrap::dropTables();
        Bootstrap::installTables();
        parent::setUp();
    }
    
    public function testInstallUserOne()
    {
        $manager = Bootstrap::getServiceManager();
        $installer = new Installer;
        $installer->setServiceLocator($manager);        
        $installer->addTask(new \Omeka\Install\Task\UserOne);
        $installer->install();
        $em = $manager->get('EntityManager');
        $userOne = $em->getRepository('\Omeka\Model\Entity\User')->find(1);        
        $this->assertEquals($userOne->getUsername(), 'userone');
        $this->assertEquals($userOne->getId(), 1);
    }
}