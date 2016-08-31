<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\BlockLayout;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the blockLayout view helper.
 */
class BlockLayoutFactory implements FactoryInterface
{
    /**
     * Create and return the blockLayout view helper
     *
     * @return BlockLayout
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new BlockLayout($services->get('Omeka\BlockLayoutManager'));
    }
}
