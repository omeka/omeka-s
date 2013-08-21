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
        $config = $serviceLocator->get('ApplicationConfig');
        return $this->createEntityManager($config);
    }
    
    public function createEntityManager($config)
    {
        if (!isset($config['doctrine']['conn'])) {
            throw new \RuntimeException('No database configuration given.');
        }
        $conn = $config['doctrine']['conn'];
        if (isset($config['doctrine']['table_prefix'])) {
            $tablePrefix = $config['doctrine']['table_prefix'];
        } else {
            $tablePrefix = 'omeka_';
        }
        if (isset($config['doctrine']['is_dev_mode'])) {
            $isDevMode = $config['doctrine']['is_dev_mode'];
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
