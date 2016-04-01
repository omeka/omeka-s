<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\DeleteConfirmForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the deleteConfirmForm view helper.
 */
class DeleteConfirmFormFactory implements FactoryInterface
{
    /**
     * Create and return the deleteConfirmForm view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return DeleteConfirmForm
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new DeleteConfirmForm($serviceLocator);
    }
}
