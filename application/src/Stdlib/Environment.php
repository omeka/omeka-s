<?php
namespace Omeka\Stdlib;

use Omeka\Module;
use Omeka\Settings\Settings;
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
     * The MariaDB minimum version
     */
    const MARIADB_MINIMUM_VERSION = '10.0.5';

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
     * @param Settings $settings
     */
    public function __construct(Connection $connection, Settings $settings)
    {
        $codeVersion = Module::VERSION;
        $dbVersion = $settings->get('version');
        if ($dbVersion // Perform this check only if Omeka is installed.
            && version_compare($dbVersion, 1, '<')
        ) {
            $this->errorMessages[] = new Message(
                'You must upgrade Omeka S to at least version 1.0.0 before upgrading to version %1$s. You are currently on version %2$s.', // @translate
                $codeVersion,
                $dbVersion
            );
        }
        if (!version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '>=')) {
            $this->errorMessages[] = new Message(
                'The installed PHP version (%1$s) is too low. Omeka requires at least version %2$s.', // @translate
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
        // MariaDB includes a fake 5.5.5- leading version in many cases to the
        // client handshake, which is what you get if you ask PDO for the server
        // version. The VERSION() function doesn't include that junk.
        $mysqlVersion = $connection->fetchColumn('SELECT VERSION()');
        if (strpos($mysqlVersion, 'MariaDB') === false) {
            if (!version_compare($mysqlVersion, self::MYSQL_MINIMUM_VERSION, '>=')) {
                $this->errorMessages[] = new Message(
                    'The installed MySQL version (%1$s) is too low. Omeka requires at least version %2$s.', // @translate
                    $mysqlVersion,
                    self::MYSQL_MINIMUM_VERSION
                );
            }
        } else {
            if (!version_compare($mysqlVersion, self::MARIADB_MINIMUM_VERSION, '>=')) {
                $this->errorMessages[] = new Message(
                    'The installed MariaDB version (%1$s) is too low. Omeka requires at least version %2$s.', // @translate
                    $mysqlVersion,
                    self::MARIADB_MINIMUM_VERSION
                );
            }
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
