<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\DataType;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the dataType view helper.
 */
class DataTypeFactory implements FactoryInterface
{
    /**
     * Create and return the dataType view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return DataType
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new DataType($serviceLocator->get('Omeka\DataTypeManager'));
    }
}
