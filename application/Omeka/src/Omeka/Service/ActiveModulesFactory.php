<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating an array of active modules.
 */
class ActiveModulesFactory implements FactoryInterface
{
    /**
     * Create an array of active modules.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return array
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        if (!$serviceLocator->get('Omeka\Installation')->isInstalled()) {
            return array();
        }

        $config = $serviceLocator->get('ApplicationConfig');
        $connection = $serviceLocator->get('Omeka\Connection');

        $table = $config['connection']['table_prefix'] . 'module';
        $statement = $connection->prepare("SELECT id FROM $table WHERE is_active = 1");
        $statement->execute();

        $activeModules = array();
        foreach ($statement->fetchAll() as $activeModule) {
            $activeModules[] = $activeModule['id'];
        }

        return $activeModules;
    }
}
