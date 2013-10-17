<?php
namespace Omeka\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Omeka\Db\Event\Listener\ResourceDiscriminatorMap;
use Omeka\Db\Event\Listener\TablePrefix;
use Omeka\Db\Event\Listener\EntityValidationErrorDetector;
use Omeka\Db\Logging\FileSqlLogger;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

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
        $config = $serviceLocator->get('Config');
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

        $emConfig = Setup::createAnnotationMetadataConfiguration(
            array(__DIR__ . '/../Model/Entity'), $isDevMode
        );
        $emConfig->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        if (isset($config['log_sql'])
            && $config['log_sql']
            && isset($config['log_path'])
            && is_file($config['log_path'])
            && is_writable($config['log_path'])
        ) {
            $emConfig->setSQLLogger(new FileSqlLogger($config['log_path']));
        }

        $em = EntityManager::create($conn, $emConfig);
        $em->getEventManager()->addEventListener(
            Events::loadClassMetadata,
            new TablePrefix($tablePrefix)
        );
        $em->getEventManager()->addEventListener(
            Events::loadClassMetadata,
            new ResourceDiscriminatorMap
        );
        return $em;
    }
}
