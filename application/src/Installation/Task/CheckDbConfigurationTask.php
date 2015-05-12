<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

/**
 * Check database configuration task.
 */
class CheckDbConfigurationTask implements TaskInterface
{
    const MYSQL_MINIMUM_VERSION = '5.5.3';

    public function perform(Installer $installer)
    {
        try {
            $connection = $installer->getServiceLocator()->get('Omeka\Connection');
            $connection->connect();
        } catch (\Exception $e) {
            $installer->addError($e->getMessage());
            return;
        }

        $version = $connection->getWrappedConnection()->getServerVersion();
        if (version_compare($version, self::MYSQL_MINIMUM_VERSION, '<')) {
            $installer->addError(sprintf(
                'Omeka requires at least MySQL version %s, but this server is running version %s.',
                self::MYSQL_MINIMUM_VERSION, $version));
        }
    }
}
