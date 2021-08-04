<?php
namespace Omeka\ServiceManager;

/**
 * Sortable interface for managed services
 *
 * Managed services should implement this interface if they can be sorted in
 * alphabetical order when using AbstractPluginManager::getRegisteredNames(true).
 */
interface SortableInterface
{
    /**
     * Get the sortable string that identifies this service, such as a human
     * readable label or title.
     *
     * @return string
     */
    public function getSortableString();
}
