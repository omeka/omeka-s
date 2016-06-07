<?php
namespace Omeka\Service;

use Omeka\Db\Migration\Manager as MigrationManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Migration manager factory.
 */
class MigrationManagerFactory implements FactoryInterface
{
    /**
     * Create the migration manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return MigrationManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = [
            'path'      => OMEKA_PATH . '/data/migrations',
            'namespace' => 'Omeka\Db\Migrations',
        ];
        $connection = $serviceLocator->get('Omeka\Connection');
        return new MigrationManager($config, $connection, $serviceLocator);
    }
}
