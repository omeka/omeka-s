<?php
namespace Omeka\Service;

use Omeka\Stdlib\Paginator;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PaginatorFactory implements FactoryInterface
{
    /**
     * Create the Paginator service.
     *
     * @return Paginator
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $settings = $serviceLocator->get('Omeka\Settings');

        $paginator = new Paginator;
        $paginator->setPerPage($settings->get('pagination_per_page', Paginator::PER_PAGE));
        return $paginator;
    }
}
