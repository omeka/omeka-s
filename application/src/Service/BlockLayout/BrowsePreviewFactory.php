<?php
namespace Omeka\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Omeka\Site\BlockLayout\BrowsePreview;
use Zend\ServiceManager\Factory\FactoryInterface;

class BrowsePreviewFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new BrowsePreview($serviceLocator->get('Omeka\ApiManager'));
    }
}
