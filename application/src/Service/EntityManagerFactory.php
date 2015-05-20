<?php
namespace Omeka\Service;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Omeka\Db\Event\Listener\ResourceDiscriminatorMap;
use Omeka\Db\Event\Listener\Utf8mb4;
use Omeka\Db\Event\Subscriber\Entity;
use Omeka\Db\Logging\FileSqlLogger;
use Omeka\Service\Exception;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the Doctrine entity manager.
 */
class EntityManagerFactory implements FactoryInterface
{
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

        if (!isset($appConfig['connection'])) {
            throw new Exception\ConfigException('Missing database connection configuration');
        }
        if (!isset($config['entity_manager'])) {
            throw new Exception\ConfigException('Missing entity manager configuration');
        }
        if (!isset($config['entity_manager']['mapping_classes_paths'])) {
            throw new Exception\ConfigException('Missing mapping classes paths configuration');
        }
        if (!isset($config['entity_manager']['resource_discriminator_map'])) {
            throw new Exception\ConfigException('Missing resource discriminator map configuration');
        }

        if (isset($config['entity_manager']['is_dev_mode'])) {
            $isDevMode = (bool) $config['entity_manager']['is_dev_mode'];
        } else {
            $isDevMode = self::IS_DEV_MODE;
        }

        $emConfig = Setup::createAnnotationMetadataConfiguration(
            $config['entity_manager']['mapping_classes_paths'], $isDevMode
        );
        // Use the underscore naming strategy to preempt potential compatibility
        // issues with the case sensitivity of various operating systems.
        // @see http://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html
        $emConfig->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        $emConfig->addFilter('visibility', 'Omeka\Db\Filter\VisibilityFilter');

        $proxyDir = OMEKA_PATH . '/data/doctrine-proxies';
        $emConfig->setProxyDir($proxyDir);

        $connection = $serviceLocator->get('Omeka\Connection');

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
        // Instantiate the visibility filter and inject the service locator.
        $em->getFilters()->enable('visibility');
        $em->getFilters()->getFilter('visibility')->setServiceLocator($serviceLocator);
        $em->getEventManager()->addEventListener(
            Events::loadClassMetadata,
            new ResourceDiscriminatorMap($config['entity_manager']['resource_discriminator_map'])
        );
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new Utf8mb4);
        $em->getEventManager()->addEventSubscriber(new Entity($serviceLocator));

        // Register a custom mapping type for an IP address.
        Type::addType('ip_address', 'Omeka\Db\Type\IpAddress');

        return $em;
    }
}
