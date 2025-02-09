<?php
namespace Omeka\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Omeka\Db\Logging\FileSqlLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

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
     * @param ContainerInterface $serviceLocator
     * @return Connection
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('ApplicationConfig');

        if (!isset($config['connection'])) {
            throw new Exception\ConfigException('Missing database connection configuration');
        }

        // Force the "generic" MySQL platform to avoid autodetecting and using exclusive features
        // of newer versions
        $platform = new MySqlPlatform;

        $config['connection']['driver'] = self::DRIVER;
        $config['connection']['charset'] = self::CHARSET;
        $config['connection']['platform'] = $platform;
        $connection = DriverManager::getConnection($config['connection']);

        // Manually-set platforms must have the event manager manually injected
        $platform->setEventManager($connection->getEventManager());

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
