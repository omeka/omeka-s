<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

/**
 * Check environment task.
 */
class CheckEnvironmentTask implements TaskInterface
{
    const PHP_MINIMUM_VERSION = '5.6';

    public static $requiredExtensions = [
        'PDO',
        'pdo_mysql',
        'xml',
    ];

    public function perform(Installer $installer)
    {
        if (version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '<')) {
            $installer->addError(sprintf(
                'The installed PHP version (%1$s) is too low. Omeka requires at least version %2$s',
                PHP_VERSION,
                self::PHP_MINIMUM_VERSION
            ));
        }

        foreach (self::$requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $installer->addError(sprintf(
                    'Omeka requires the PHP extension %s, and it is not loaded.',
                    $ext
                ));
            }
        }

        $this->testRandomGeneration();
    }

    /**
     * Test if we can successfully generate random data. If not, refuse to install.
     */
    protected function testRandomGeneration()
    {
        try {
            random_bytes(32);
        } catch (\Exception $e) {
            $installer->addError('Omeka is unable to securely generate random numbers.');
        }
    }
}
