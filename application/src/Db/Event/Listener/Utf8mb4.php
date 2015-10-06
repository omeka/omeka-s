<?php
namespace Omeka\Db\Event\Listener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;

/**
 * Load the resource discriminator map dynamically.
 */
class Utf8mb4
{
    /**
     * Set the charset to utf8mb4.
     *
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $metadata->table['options']['collate'] = 'utf8mb4_unicode_ci';
        $metadata->table['options']['charset'] = 'utf8mb4';
    }
}
