<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Media;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

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
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Media(
            $services->get('Omeka\Media\Ingester\Manager'),
            $services->get('Omeka\Media\Renderer\Manager')
        );
    }
}
