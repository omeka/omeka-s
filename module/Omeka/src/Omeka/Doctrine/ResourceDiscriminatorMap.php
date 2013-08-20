<?php
namespace Omeka\Doctrine;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;

/**
 * Load the Resource discriminator map dynamically.
 */
class ResourceDiscriminatorMap
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
