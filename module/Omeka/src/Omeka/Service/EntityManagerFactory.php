<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Events;
use Omeka\Doctrine\TablePrefix;
use Omeka\Doctrine\ResourceDiscriminatorMap;

class EntityManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['db'])) {
            throw new RuntimeException('No database configuration given.');
        }
        $dbParams = $config['db'];

        if (isset($dbParams['prefix'])) {
            $tablePrefix = $dbParams['prefix'];
            unset($dbParams['prefix']);
        } else {
            $tablePrefix = 'omeka_';
        }

        $isDevMode = true;
        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . '/../Model'), $isDevMode);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        $em = EntityManager::create($dbParams, $config);
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new TablePrefix($tablePrefix));
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new ResourceDiscriminatorMap);

        return $em;
    }
}
