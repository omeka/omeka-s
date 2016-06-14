<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\BlockThumbnailTypeSelect;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the blockThumbnailTypeSelect view helper.
 */
class BlockThumbnailTypeSelectFactory implements FactoryInterface
{
    /**
     * Create and return the blockThumbnailTypeSelect view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return BlockThumbnailTypeSelect
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new BlockThumbnailTypeSelect($serviceLocator->get('Omeka\File\Manager'));
    }
}
