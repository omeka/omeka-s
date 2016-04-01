<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\BlockLayout;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the blockLayout view helper.
 */
class BlockLayoutFactory implements FactoryInterface
{
    /**
     * Create and return the blockLayout view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return BlockLayout
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new BlockLayout($serviceLocator->get('Omeka\BlockLayoutManager'));
    }
}
