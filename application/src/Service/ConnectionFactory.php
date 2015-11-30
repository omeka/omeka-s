<?php
namespace Omeka\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Omeka\Db\Logging\FileSqlLogger;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DBAL connection.
 */
class ConnectionFactory implements FactoryInterface
{
    const DRIVER = 'pdo_mysql';
    const CHARSET = 'utf8mb4';

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
        $config['connection']['charset'] = self::CHARSET;
        $connection = DriverManager::getConnection($config['connection']);

        if (isset($config['connection']['log_path'])
            && is_file($config['connection']['log_path'])
            && is_writable($config['connection']['log_path'])
        ) {
            $connection->getConfiguration()
                ->setSQLLogger(new FileSqlLogger($config['connection']['log_path']));
        }

        return $connection;
    }
}
