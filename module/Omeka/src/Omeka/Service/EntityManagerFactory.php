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
        return $this->createEntityManager($config);
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
        if (!isset($config['entity_manager']['conn'])) {
            throw new \RuntimeException('No database configuration given.');
        }
        $conn = $config['entity_manager']['conn'];
        if (isset($config['table_prefix'])) {
            $tablePrefix = $config['entity_manager']['table_prefix'];
        } else {
            $tablePrefix = 'omeka_';
        }
        if (isset($config['entity_manager']['is_dev_mode'])) {
            $isDevMode = $config['entity_manager']['is_dev_mode'];
        } else {
            $isDevMode = false;
        }

        $emConfig = Setup::createAnnotationMetadataConfiguration(
            array(__DIR__ . '/../Model/Entity'), $isDevMode
        );
        $emConfig->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        if (isset($config['loggers']['sql']['log'])
            && $config['loggers']['sql']['log']
            && isset($config['loggers']['sql']['path'])
            && is_file($config['loggers']['sql']['path'])
            && is_writable($config['loggers']['sql']['path'])
        ) {
            $emConfig->setSQLLogger(new FileSqlLogger($config['loggers']['sql']['path']));
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
