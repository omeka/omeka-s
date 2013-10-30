<?php
namespace Omeka\Service;

use Omeka\Install\Installer;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating Omeka installer
 *
 */
class InstallerFactory implements FactoryInterface
{
    /**
     * Create the installer service
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Installer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        
        $installer = new Installer;
        $tasks = $config['install']['tasks'];
        foreach($tasks as $task) {
            $installer->addTask(new $task);
        }
        
        return $installer;
    }
}