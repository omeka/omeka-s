<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Pagination;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the pagination view helper.
 */
class PaginationFactory implements FactoryInterface
{
    /**
     * Create and return the pagination view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return Pagination
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new Pagination($serviceLocator->get('Omeka\Paginator'));
    }
}
