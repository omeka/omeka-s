<?php
namespace Omeka\Test;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;

/**
 * Helper methods for building commonly used mock objects for testing.
 *
 * The idea is to first set the mock objects to variables and then set
 * their expectations according to the specific test case.
 */
class MockBuilder extends TestCase
{
    /**
     * Get a mock Zend\ServiceManager\ServiceManager (ServiceLocator) object.
     *
     * Pass a mock service object that should be accessible via the mocked 
     * ServiceManager::get().
     *
     * @param mixed $service
     * @return ServiceManager
     */
    public function getServiceManager($service = null)
    {
        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        if ($service instanceof EntityManager) {
            $serviceManager->expects($this->once())
                ->method('get')
                ->with($this->equalTo('EntityManager'))
                ->will($this->returnValue($service));
        }
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
    public function getQuery(array $methods = array())
    {
        $query = $this->getMock('stdClass', $methods);
        return $query;
    }
}
