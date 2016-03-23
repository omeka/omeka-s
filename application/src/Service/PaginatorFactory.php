<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PaginatorFactory implements FactoryInterface
{
    /**
     * Create the Paginator service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Paginator
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');

        $paginator = new Paginator;
        $paginator->setPerPage($settings->get('pagination_per_page', Paginator::PER_PAGE));
        return $paginator;
    }
}
