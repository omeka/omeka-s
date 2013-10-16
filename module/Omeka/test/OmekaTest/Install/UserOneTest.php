<?php
namespace OmekaTest\Install;

use Omeka\Install\Installer;
use Omeka\Install\TaskAbstract;
use Omeka\Install\TaskResult;
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
        $installer->addTask(new \Omeka\Install\TaskCreateUserOne);
        $installer->install();
        $em = Bootstrap::getEntityManager();
        $userOne = $em->getRepository('\\Omeka\\Model\\Entity\\User')->find(1);        
        $this->assertEquals($userOne->getUsername(), 'userone');
        $this->assertEquals($userOne->getId(), 1);
    }
}