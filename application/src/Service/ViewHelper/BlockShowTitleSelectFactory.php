<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\BlockShowTitleSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the blockShowTitleeSelect view helper.
 */
class BlockShowTitleSelectFactory implements FactoryInterface
{
    /**
     * Create and return the blockShowTitleSelect view helper
     *
     * @return BlockShowTitleSelect
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new BlockShowTitleSelect($services->get('Omeka\File\Manager'));
    }
}
