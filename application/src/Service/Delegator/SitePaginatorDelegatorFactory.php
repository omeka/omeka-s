<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

class SitePaginatorDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $paginator = $callback();
        $settings = $container->get('Omeka\Settings\Site');
        $perPage = $settings->get('pagination_per_page');
        if ($perPage) {
            $paginator->setPerPage($perPage);
        }
        return $paginator;
    }
}
