<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\SearchUserFilters;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the searchUserFilters view helper.
 */
class SearchUserFiltersFactory implements FactoryInterface
{
    /**
     * Create and return the searchUserFilters view helper
     *
     * @return SearchUserFilters
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $roleLabels = $services->get('Omeka\Acl')->getRoleLabels();
        return new SearchUserFilters($roleLabels);
    }
}
