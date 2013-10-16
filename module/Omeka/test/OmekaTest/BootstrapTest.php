<?php

namespace OmekaTest;

use OmekaTest\Bootstrap;
use Omeka\Service\EntityManagerFactory;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    protected $connection;
    
    public function setUp()
    {
        $factory = new EntityManagerFactory;
        $em = $factory->createEntityManager(Bootstrap::getEntityManagerConfig());
        $this->connection = $em->getConnection();        
        parent::setUp();
    }
    
    public function testApplicationConfigIsArray()
    {
        $config = Bootstrap::getApplicationConfig();
        $this->assertTrue(is_array($config));
    }
    
    public function testInstallTables()
    {
        Bootstrap::installTables();
        $tables = $this->connection->getSchemaManager()->listTableNames();
        $this->assertNotEmpty($tables);
    }
    
    public function testDropTables()
    {
        $tables = $this->connection->getSchemaManager()->listTableNames();        
        Bootstrap::dropTables();
        $tables = $this->connection->getSchemaManager()->listTableNames();
        $this->assertEmpty($tables);
    }
}