<?php
namespace Omeka\Service;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Omeka\Db\Event\Listener\ResourceDiscriminatorMap;
use Omeka\Db\Event\Listener\Utf8mb4;
use Omeka\Db\Event\Subscriber\Entity;
use Omeka\Db\ProxyAutoloader;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

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
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
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

        if (extension_loaded('apcu') && !$isDevMode) {
            $cache = new ApcuCache();
        } else {
            $cache = new ArrayCache();
        }

        // Set up the entity manager configuration.
        $emConfig = Setup::createAnnotationMetadataConfiguration(
            $config['entity_manager']['mapping_classes_paths'], $isDevMode, null, $cache
        );
        $emConfig->setProxyDir(OMEKA_PATH . '/application/data/doctrine-proxies');

        // Use the underscore naming strategy to preempt potential compatibility
        // issues with the case sensitivity of various operating systems.
        // @see http://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html
        $emConfig->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        // Add SQL filters.
        foreach ($config['entity_manager']['filters'] as $name => $className) {
            $emConfig->addFilter($name, $className);
        }

        // Add user defined functions.
        $emConfig->setCustomNumericFunctions($config['entity_manager']['functions']['numeric']);
        $emConfig->setCustomStringFunctions($config['entity_manager']['functions']['string']);
        $emConfig->setCustomDatetimeFunctions($config['entity_manager']['functions']['datetime']);

        // Load proxies from different directories
        // HACK: Doctrine takes an integer here and just happens to do nothing (which is
        // what we want) if the number is not one of the defined proxy generation
        // constants.
        $emConfig->setAutoGenerateProxyClasses(-1);
        ProxyAutoloader::register($config['entity_manager']['proxy_paths'],
            $emConfig->getProxyNamespace());

        // Set up the entity manager.
        $connection = $serviceLocator->get('Omeka\Connection');
        $em = EntityManager::create($connection, $emConfig);
        $em->getEventManager()->addEventListener(
            Events::loadClassMetadata,
            new ResourceDiscriminatorMap($config['entity_manager']['resource_discriminator_map'])
        );
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new Utf8mb4);
        $em->getEventManager()->addEventSubscriber(new Entity($serviceLocator->get('EventManager')));
        // Instantiate the visibility filter and inject the service locator.
        $em->getFilters()->enable('resource_visibility');
        $em->getFilters()->getFilter('resource_visibility')->setServiceLocator($serviceLocator);

        // Register a custom mapping type for an IP address.
        if (!Type::hasType('ip_address')) {
            Type::addType('ip_address', 'Omeka\Db\Type\IpAddress');
        }

        return $em;
    }
}
