<?php
namespace Omeka\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Events;
use Omeka\Db\Event\Listener\CreateTableOverride;
use Omeka\Db\Logging\FileSqlLogger;
use Zend\ServiceManager\Factory\FactoryInterface;
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
     * @param ServiceLocatorInterface $serviceLocator
     * @return Connection
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
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

        $eventManager = $connection->getEventManager();
        $eventManager->addEventListener(Events::onSchemaCreateTable, new CreateTableOverride);

        return $connection;
    }
}
