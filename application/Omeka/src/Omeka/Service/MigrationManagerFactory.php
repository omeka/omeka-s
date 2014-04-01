<?php
namespace Omeka\Service;

use Omeka\Db\Migration\Exception;
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
        $config = array(
            'path'      => OMEKA_PATH . '/data/migrations',
            'namespace' => 'Omeka\Db\Migrations',
            'entity'    => 'Omeka\Model\Entity\Migration',
        );
        return new MigrationManager($config);
    }
}
