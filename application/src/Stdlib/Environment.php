<?php
namespace Omeka\Stdlib;

use Doctrine\DBAL\Connection;

class Environment
{
    /**
     * The PHP minimum version
     */
    const PHP_MINIMUM_VERSION = '7.1.0';

    /**
     * The MySQL minimum version
     */
    const MYSQL_MINIMUM_VERSION = '5.6.4';

    /**
     * The required PHP extensions
     */
    const PHP_REQUIRED_EXTENSIONS = ['fileinfo', 'mbstring', 'PDO', 'pdo_mysql', 'xml'];

    /**
     * @var array Environment error messages
     */
    protected $errorMessages = [];

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        if (!version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '>=')) {
            $this->errorMessages[] = new Message(
                'The installed PHP version (%s) is too low. Omeka requires at least version %s.', // @translate
                PHP_VERSION,
                self::PHP_MINIMUM_VERSION
            );
        }
        foreach (self::PHP_REQUIRED_EXTENSIONS as $extension) {
            if (!extension_loaded($extension)) {
                $this->errorMessages[] = new Message(
                    'Omeka requires the PHP extension %s, but it is not loaded.', // @translate
                    $extension
                );
            }
        }
        try {
            $connection->connect();
        } catch (\Exception $e) {
            $this->errorMessages[] = new Message($e->getMessage());
            // Error establishing a connection, no need to check MySQL version.
            return;
        }
        $mysqlVersion = $connection->getWrappedConnection()->getServerVersion();
        if (!version_compare($mysqlVersion, self::MYSQL_MINIMUM_VERSION, '>=')) {
            $this->errorMessages[] = new Message(
                'The installed MySQL version (%s) is too low. Omeka requires at least version %s.', // @translate
                $mysqlVersion,
                self::MYSQL_MINIMUM_VERSION
            );
        }
    }

    /**
     * Is the environment compatible with Omeka S?
     *
     * @return bool
     */
    public function isCompatible()
    {
        return !$this->errorMessages;
    }

    /**
     * Get environment error messages.
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }
}
