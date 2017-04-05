<?php
namespace Omeka\Service;

use Omeka\Db\Migration\Manager as MigrationManager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Migration manager factory.
 */
class MigrationManagerFactory implements FactoryInterface
{
    /**
     * Create the migration manager service.
     *
     * @return MigrationManager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = [
            'path' => OMEKA_PATH . '/application/data/migrations',
            'namespace' => 'Omeka\Db\Migrations',
        ];
        $connection = $serviceLocator->get('Omeka\Connection');
        return new MigrationManager($config, $connection, $serviceLocator);
    }
}
