<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\BlockThumbnailTypeSelect;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the blockThumbnailTypeSelect view helper.
 */
class BlockThumbnailTypeSelectFactory implements FactoryInterface
{
    /**
     * Create and return the blockThumbnailTypeSelect view helper
     *
     * @return BlockThumbnailTypeSelect
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $thumbnailTypes = array_keys($config['file_manager']['thumbnail_types']);
        return new BlockThumbnailTypeSelect($thumbnailTypes);
    }
}
