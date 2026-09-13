<?php declare(strict_types=1);

namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;
use Omeka\Stdlib\SecretKey;

/**
 * Generate the application secret key on install.
 *
 * When a key is already resolved (environment or file) nothing is done.
 */
class CreateSecretKeyTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $services = $installer->getServiceLocator();
        if (SecretKey::resolve()) {
            return;
        }

        $logger = $services->get('Omeka\Logger');
        if (SecretKey::store(SecretKey::generate())) {
            $logger->info('A secret key was generated in config/secret_key.php to encrypt secrets.'); // @translate
            return;
        }

        $message = 'The secret key could not be created: set it manually in config/secret_key.php or set the environment variable "OMEKA_SECRET_KEY" to encrypt secrets (module api keys, etc.).'; // @translate
        $logger->warn($message);
        $services->get('ControllerPluginManager')->get('messenger')->addWarning($message);
    }
}
