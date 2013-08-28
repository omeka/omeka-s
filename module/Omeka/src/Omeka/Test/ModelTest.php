<?php
namespace Omeka\Test;

use PHPUnit_Framework_TestCase;
use OmekaTest\Bootstrap;
use Omeka\Service\EntityManagerFactory;

abstract class ModelTest extends PHPUnit_Framework_TestCase
{
    protected $em;
    protected $className;
    
    public function setUp()
    {
        //if a test fails and the EM closes, there's no way to reopen it, which makes subsequent tests
        //fail. So create it anew for each test class
        
        $factory = new EntityManagerFactory;
        $this->em = $factory->createEntityManager(Bootstrap::getEntityManagerConfig());        
        parent::setUp();
    }
    
    public function tearDown()
    {
        //empty the test database each time around
        try {
            $connection = $this->em->getConnection();
            $platform   = $connection->getDatabasePlatform();
            $cmd = $this->em->getClassMetadata($this->className);
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeUpdate($platform->getTruncateTableSQL($cmd->getTableName(), true));
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            $connection->rollback();
        }
        parent::tearDown();
    }    
    
    public function testCrud()
    {
        //create and read
        $className = $this->className;
        $entity = new $className;
        $this->setUpNewEntity($entity);
        $this->em->persist($entity);
        $this->em->flush();
        $newEntity = $this->em->getRepository($this->className)->find(1);
        $this->assertNotNull($newEntity);
        
        //update
        $this->updateNewEntity($entity);
        
        //delete
        $this->em->remove($entity);
        $this->em->flush();
        $deleted = $this->em->getRepository($this->className)->find(1);
        $this->assertNull($deleted);        
    }
    
    protected function setUpNewEntity($entity) {}
    abstract protected function updateNewEntity($entity);
}