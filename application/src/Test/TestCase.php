<?php
namespace Omeka\Test;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Zend\ServiceManager\ServiceManager;

/**
 * Helper methods for building commonly used mock objects for testing.
 *
 * The idea is to first set the mock objects to variables and then set
 * their expectations according to the specific test case.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Get a mock Zend\ServiceManager\ServiceManager (ServiceLocator) object.
     *
     * Pass mock service objects that should be accessible via the mocked
     * ServiceManager::get().
     *
     * @param array $services Where the key is the service name and the value
     * is the mock service object
     * @return ServiceManager
     */
    public function getServiceManager(array $services = [])
    {
        $serviceManager = $this->createMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->expects($this->any())
            ->method('get')
            ->with($this->callback(function ($subject) use ($services) {
                return array_key_exists($subject, $services);
            }))
            ->will($this->returnCallback(function ($subject) use ($services) {
                return $services[$subject];
            }));
        return $serviceManager;
    }

    /**
     * Get a mock Doctrine\ORM\EntityManager object.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        return $entityManager;
    }

    public function getEntityRepository()
    {
        $entityRepostory = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        return $entityRepostory;
    }

    /**
     * Get a mock Doctrine\ORM\QueryBuilder object.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        return $queryBuilder;
    }

    /**
     * Get a mock Doctrine\ORM\Query object.
     *
     * Doctrine\ORM\Query cannot be mocked because it is declared "final".
     * Instead, mock stdClass and assign the requisite methods.
     *
     * @param array $methods
     * @return stdClass
     */
    public function getQuery(array $methods = [])
    {
        $query = $this->getMock('stdClass', $methods);
        return $query;
    }
}
