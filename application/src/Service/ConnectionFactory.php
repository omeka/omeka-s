<?php
namespace Omeka\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Omeka\Db\Connection\SqliteCompatConnection;
use Omeka\Db\Logging\FileSqlLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Factory for creating the DBAL connection.
 */
class ConnectionFactory implements FactoryInterface
{
    const DRIVER_MYSQL = 'pdo_mysql';
    const DRIVER_SQLITE = 'pdo_sqlite';
    const CHARSET = 'utf8mb4';

    /**
     * Create the DBAL connection service.
     *
     * @param ContainerInterface $serviceLocator
     * @return Connection
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        $config = $serviceLocator->get('ApplicationConfig');

        if (!isset($config['connection'])) {
            throw new Exception\ConfigException('Missing database connection configuration');
        }

        $driver = $config['connection']['driver'] ?? self::DRIVER_MYSQL;

        if ($driver === self::DRIVER_SQLITE) {
            $platform = new SqlitePlatform;
            $config['connection']['driver'] = self::DRIVER_SQLITE;
            $config['connection']['platform'] = $platform;
            // Remove MySQL-specific options that don't apply to SQLite
            unset($config['connection']['charset']);
            $config['connection']['wrapperClass'] = SqliteCompatConnection::class;
            $connection = DriverManager::getConnection($config['connection']);
            $platform->setEventManager($connection->getEventManager());
            // Enable foreign keys and WAL mode for better performance
            $connection->exec('PRAGMA foreign_keys = ON');
            $connection->exec('PRAGMA journal_mode = WAL');
        } else {
            $platform = new MySqlPlatform;
            $config['connection']['driver'] = self::DRIVER_MYSQL;
            $config['connection']['charset'] = self::CHARSET;
            $config['connection']['platform'] = $platform;
            $connection = DriverManager::getConnection($config['connection']);
            $platform->setEventManager($connection->getEventManager());
        }

        if (isset($config['connection']['log_path'])
            && is_file($config['connection']['log_path'])
            && is_writable($config['connection']['log_path'])
        ) {
            $connection->getConfiguration()
                ->setSQLLogger(new FileSqlLogger($config['connection']['log_path']));
        }

        return $connection;
    }

    /**
     * Check if the given connection uses SQLite.
     */
    public static function isSqlite(Connection $connection): bool
    {
        return $connection->getDatabasePlatform() instanceof SqlitePlatform;
    }
}
