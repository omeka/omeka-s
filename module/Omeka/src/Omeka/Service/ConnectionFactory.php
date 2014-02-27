<?php
namespace Omeka\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DBAL connection.
 */
class ConnectionFactory implements FactoryInterface
{
    const CONNECTION_DRIVER = 'pdo_mysql';
    const CONNECTION_CHARSET = 'utf8';

    /**
     * Create the DBAL connection service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Connection
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $appConfig = $serviceLocator->get('ApplicationConfig');

        if (!isset($appConfig['connection'])) {
            throw new \RuntimeException('No database connection configuration given.');
        }

        $appConfig['connection']['driver'] = self::CONNECTION_DRIVER;
        if (!isset($config['charset'])) {
            $appConfig['connection']['charset'] = self::CONNECTION_CHARSET;
        }

        return DriverManager::getConnection($appConfig['connection']);
    }
}
