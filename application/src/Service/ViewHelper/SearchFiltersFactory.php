<?php declare(strict_types=1);

namespace Omeka\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\View\Helper\SearchFilters;

/**
 * Service factory for SearchFilters view helper.
 */
class SearchFiltersFactory implements FactoryInterface
{
    /**
     * Create and return the SearchFilters view helper.
     *
     * @return SearchFilters
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SearchFilters(
            $services->get('Omeka\ApiAdapterManager')->get('resources')
        );
    }
}
