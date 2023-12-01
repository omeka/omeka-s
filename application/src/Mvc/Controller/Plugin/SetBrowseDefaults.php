<?php
namespace Omeka\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for setting browse default parameters.
 */
class SetBrowseDefaults extends AbstractPlugin
{
    /**
     * Set the sort and page parameters to the request, if not already set.
     *
     * @param string $sortBy
     * @param string $sortOrder
     * @param int $page
     * @return \Laminas\Stdlib\Parameters
     */
    public function __invoke($sortBy, $sortOrder = 'desc', $page = 1)
    {
        $query = $this->getController()->getRequest()->getQuery();
        // Set the sort_by_default flag if the request doesn't pass a sort_by.
        $query->set('sort_by_default', (null === $query->get('sort_by') || '' === $query->get('sort_by')) ? '' : null);
        $query->set('sort_by', $query->get('sort_by', $sortBy));
        $query->set('sort_order', $query->get('sort_order', $sortOrder));
        $query->set('page', $query->get('page', $page));
    }
}
