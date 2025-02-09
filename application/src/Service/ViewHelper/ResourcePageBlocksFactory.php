<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ResourcePageBlocks;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourcePageBlocksFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $currentTheme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $blockLayoutManager = $services->get('Omeka\ResourcePageBlockLayoutManager');
        $resourcePageBlocks = $blockLayoutManager->getResourcePageBlocks($currentTheme);
        return new ResourcePageBlocks($blockLayoutManager, $resourcePageBlocks);
    }
}
