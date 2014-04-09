<?php
namespace Omeka\Service;

use Omeka\Installtion\Exception;
use Omeka\Installation\Manager as InstallationManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Installation manager factory.
 */
class InstallationManagerFactory implements FactoryInterface
{
    /**
     * Create the installation manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return InstallationManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('ApplicationConfig');
        if (!isset($config['installation_manager']['tasks'])) {
            throw new Exception\ConfigException(
                'The configuration has no registered installation tasks.'
            );
        }
        $installationManager = new InstallationManager;
        $installationManager->setServiceLocator($serviceLocator);
        $installationManager->registerTasks($config['installation_manager']['tasks']);
        return $installationManager;
    }
}
