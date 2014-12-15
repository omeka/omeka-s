<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Manager;

/**
 * Check environment task.
 */
class CheckEnvironmentTask implements TaskInterface
{
    const PHP_MINIMUM_VERSION = '5.4';

    public static $requiredExtensions = array(
        'PDO',
        'pdo_mysql',
    );

    public function perform(Manager $manager)
    {
        if (version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '<')) {
            $manager->addError(sprintf(
                'The installed PHP version (%1$s) is too low. Omeka requires at least version %2$s',
                PHP_VERSION,
                self::PHP_MINIMUM_VERSION
            ));
        }

        foreach (self::$requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $manager->addError(sprintf(
                    'Omeka requires the PHP extension %s, and it is not loaded.',
                    $ext
                ));
            }
        }
    }
}
