<?php
namespace Omeka\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Omeka\Service\Exception;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DBAL connection.
 */
class ConnectionFactory implements FactoryInterface
{
    const DRIVER = 'pdo_mysql';
    const CHARSET = 'utf8';

    /**
     * Create the DBAL connection service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Connection
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('ApplicationConfig');

        if (!isset($config['connection'])) {
            throw new Exception\ConfigException('Missing database connection configuration');
        }

        $config['connection']['driver'] = self::DRIVER;
        if (!isset($config['connection']['charset'])) {
            $config['connection']['charset'] = self::CHARSET;
        }

        return DriverManager::getConnection($config['connection']);
    }
}
