<?php
namespace Omeka\Stdlib;

use Zend\ServiceManager\ServiceLocatorInterface;

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
     * @param ServiceLocatorInterface $services
     */
    public function __construct(ServiceLocatorInterface $services)
    {
        $translator = $services->get('MvcTranslator');
        if (!version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '>=')) {
            $this->errorMessages[] = sprintf(
                $translator->translate('The installed PHP version (%s) is too low. Omeka requires at least version %s.'),
                PHP_VERSION,
                self::PHP_MINIMUM_VERSION
            );
        }
        foreach (self::PHP_REQUIRED_EXTENSIONS as $extension) {
            if (!extension_loaded($extension)) {
                $this->errorMessages[] = sprintf(
                    $translator->translate('Omeka requires the PHP extension %s, but it is not loaded.'),
                    $extension
                );
            }
        }
        try {
            $connection = $services->get('Omeka\Connection');
            $connection->connect();
        } catch (\Exception $e) {
            $this->errorMessages[] = $e->getMessage();
            // Error establishing a connection, no need to check MySQL version.
            return;
        }
        $mysqlVersion = $connection->getWrappedConnection()->getServerVersion();
        if (!version_compare($mysqlVersion, self::MYSQL_MINIMUM_VERSION, '>=')) {
            $this->errorMessages[] = sprintf(
                $translator->translate('The installed MySQL version (%s) is too low. Omeka requires at least version %s.'),
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
