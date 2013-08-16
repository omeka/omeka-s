<?php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Events;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;

ini_set('display_errors', 1);
require_once 'vendor/autoload.php';

$conn = array('driver' => 'pdo_sqlite', 'path' => 'db.sqlite');

$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . '/src'), $isDevMode);
$config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));

/**
 * Prefix table names.
 */
class TablePrefix
{
    protected $prefix = '';

    public function __construct($prefix)
    {
        $this->prefix = (string) $prefix;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $classMetadata->setTableName($this->prefix . $classMetadata->getTableName());
        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY) {
                $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
            }
        }
    }
}

/**
 * Load the Resource discriminator map dynamically.
 */
class LoadResourceDiscriminatorMap
{
    protected $defaultResources = array(
        'Item' => 'Item', 
        'Media' => 'Media', 
        'ItemSet' => 'ItemSet', 
    );
    
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $classMetadata = $event->getClassMetadata();
        if ('Resource' == $classMetadata->name) {
            // Load default resources.
            $classMetadata->discriminatorMap = $this->defaultResources;
            
            // Load plugin resources dynamically.
            // $this->loadPluginResources($classMetadata);
        }
    }
}

$em = EntityManager::create($conn, $config);
$em->getEventManager()->addEventListener(Events::loadClassMetadata, new TablePrefix('omeka_'));
$em->getEventManager()->addEventListener(Events::loadClassMetadata, new LoadResourceDiscriminatorMap);
