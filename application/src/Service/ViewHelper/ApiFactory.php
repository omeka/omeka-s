<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Api;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the api view helper.
 */
class ApiFactory implements FactoryInterface
{
    /**
     * Create and return the api view helper
     *
     * @return Api
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Api($services->get('Omeka\ApiManager'));
    }
}
