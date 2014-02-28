<?php
namespace Omeka\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Omeka\Db\Event\Listener\ResourceDiscriminatorMap;
use Omeka\Db\Event\Listener\TablePrefix;
use Omeka\Db\Logging\FileSqlLogger;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the Doctrine entity manager.
 */
class EntityManagerFactory implements FactoryInterface
{
    const TABLE_PREFIX = 'omeka_';
    const IS_DEV_MODE = false;

    /**
     * Create the entity manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return EntityManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $appConfig = $serviceLocator->get('ApplicationConfig');
        $config = $serviceLocator->get('Config');

        if (!isset($config['entity_manager']) || !isset($appConfig['connection'])) {
            throw new \RuntimeException('No entity manager configuration given.');
        }

        if (isset($appConfig['connection']['table_prefix'])) {
            $tablePrefix = $appConfig['connection']['table_prefix'];
        } else {
            $tablePrefix = self::TABLE_PREFIX;
        }
        if (isset($config['entity_manager']['is_dev_mode'])) {
            $isDevMode = (bool) $config['entity_manager']['is_dev_mode'];
        } else {
            $isDevMode = self::IS_DEV_MODE;
        }

        $emConfig = Setup::createAnnotationMetadataConfiguration(
            array(__DIR__ . '/../Model/Entity'), $isDevMode
        );
        // Use the underscore naming strategy to preempt potential compatibility
        // issues with the case sensitivity of various operating systems.
        // @see http://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html
        $emConfig->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        $proxyDir = OMEKA_PATH . '/data/doctrine-proxies';
        $emConfig->setProxyDir($proxyDir);

        $connection = $serviceLocator->get('Connection');

        if (isset($config['loggers']['sql']['log'])
            && $config['loggers']['sql']['log']
            && isset($config['loggers']['sql']['path'])
            && is_file($config['loggers']['sql']['path'])
            && is_writable($config['loggers']['sql']['path'])
        ) {
            $connection
                ->getConfiguration()
                ->setSQLLogger(new FileSqlLogger($config['loggers']['sql']['path']));
        }

        $em = EntityManager::create($connection, $emConfig);
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
