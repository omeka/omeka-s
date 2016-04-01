<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Media;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the media view helper.
 */
class MediaFactory implements FactoryInterface
{
    /**
     * Create and return the media view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return Media
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        return new Media(
            $serviceLocator->get('Omeka\MediaIngesterManager'),
            $serviceLocator->get('Omeka\MediaRendererManager')
        );
    }
}
