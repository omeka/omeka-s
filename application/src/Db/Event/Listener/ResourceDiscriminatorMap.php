<?php
namespace Omeka\Db\Event\Listener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;

/**
 * Load the Resource discriminator map dynamically.
 */
class ResourceDiscriminatorMap
{
    protected $defaultResources = array(
        'Omeka\Model\Entity\Item' => 'Omeka\Model\Entity\Item', 
        'Omeka\Model\Entity\Media' => 'Omeka\Model\Entity\Media', 
        'Omeka\Model\Entity\ItemSet' => 'Omeka\Model\Entity\ItemSet', 
    );
    
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $classMetadata = $event->getClassMetadata();
        if ('Omeka\Model\Entity\Resource' != $classMetadata->name) {
            return;
        }
        // Load default resources.
        $classMetadata->discriminatorMap = $this->defaultResources;
        
        // Load plugin resources dynamically.
        // $this->loadPluginResources($classMetadata);
    }
}
