<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Pagination;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the pagination view helper.
 */
class PaginationFactory implements FactoryInterface
{
    /**
     * Create and return the pagination view helper
     *
     * @return Pagination
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Pagination($services->get('Omeka\Paginator'));
    }
}
