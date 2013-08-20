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
        $dbParams = array(
            'driver'   => 'pdo_mysql',
            'user'     => '',
            'password' => '',
            'dbname'   => '',
        );

        $isDevMode = true;
        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . '/../Model'), $isDevMode);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

        $em = EntityManager::create($dbParams, $config);
        $tablePrefix = 'omeka_';
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new TablePrefix($tablePrefix));
        $em->getEventManager()->addEventListener(Events::loadClassMetadata, new ResourceDiscriminatorMap);

        return $em;
    }
}
