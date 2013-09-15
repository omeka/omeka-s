<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Events;
use Omeka\Db\Event\Listener\TablePrefix;
use Omeka\Db\Event\Listener\ResourceDiscriminatorMap;

/**
 * Factory for creating the Doctrine entity manager.
 */
class EntityManagerFactory implements FactoryInterface
{
    /**
     * Create the entity manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return EntityManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('ApplicationConfig');
        if (!isset($config['entity_manager'])) {
            throw new \RuntimeException('No database configuration given.');
        }
        return $this->createEntityManager($config['entity_manager']);
    }

    /**
     * Create the entity manager object.
     * 
     * Use this method to create the entity manager outside ZF2 MVC.
     * 
     * @param array $config
     * @return EntityManager
     */
    public function createEntityManager(array $config)
    {
        if (!isset($config['conn'])) {
            throw new \RuntimeException('No database configuration given.');
        }
        $conn = $config['conn'];
        if (isset($config['table_prefix'])) {
            $tablePrefix = $config['table_prefix'];
        } else {
            $tablePrefix = 'omeka_';
        }
        if (isset($config['is_dev_mode'])) {
            $isDevMode = $config['is_dev_mode'];
        } else {
            $isDevMode = false;
        }

        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . '/../Model'), $isDevMode);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        $em = EntityManager::create($conn, $config);
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new TablePrefix($tablePrefix));
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new ResourceDiscriminatorMap);

        return $em;
    }
}
