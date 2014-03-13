<?php
namespace Omeka\Installation;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Installation implements ServiceLocatorAwareInterface
{
    const CHECK_TABLE = 'user';

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Check whether Omeka is currently installed.
     */
    public function isInstalled()
    {
        $connection = $this->getServiceLocator()->get('Connection');
        $config = $this->getServiceLocator()->get('ApplicationConfig');
        $tables = $connection->getSchemaManager()->listTableNames();
        $checkTable = $config['connection']['table_prefix'] . self::CHECK_TABLE;
        return in_array($checkTable, $tables);
    }

    /**
     * Set the service locator.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * Get the service locator.
     * 
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
