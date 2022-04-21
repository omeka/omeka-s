<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation;
use Omeka\Site\ResourcePageBlockLayout\Manager;
use Omeka\Site\Theme\Theme;
use Laminas\View\Helper\AbstractHelper;

class ResourcePageBlocks extends AbstractHelper
{
    public function __construct(Manager $blockLayoutManager, array $resourcePageBlocks)
    {
        $this->blockLayoutManager = $blockLayoutManager;
        $this->resourcePageBlocks = $resourcePageBlocks;
    }

    /**
     * Return the markup for a region of the resource page.
     *
     * @param Representation\AbstractResourceEntityRepresentation $resource
     * @param string $regionName
     * @return ResourcePageBlocks
     */
    public function __invoke(Representation\AbstractResourceEntityRepresentation $resource, $regionName = 'main')
    {
        $view = $this->getView();
        $resourceClass = get_class($resource);
        switch ($resourceClass) {
            case Representation\ItemRepresentation::class:
                $resourceName = 'items';
                break;
            case Representation\ItemSetRepresentation::class:
                $resourceName = 'item_sets';
                break;
            case Representation\MediaRepresentation::class:
                $resourceName = 'media';
                break;
            default:
                return '<!-- ' . sprintf($view->translate('Resource page blocks error: invalid resource "%s"'), $resourceClass) . ' -->';
        }
        if (!isset($this->resourcePageBlocks[$resourceName])) {
            return '<!-- ' . sprintf($view->translate('Resource page blocks error: resource not supported "%s"'), $resourceName) . ' -->';
        }
        if (!isset($this->resourcePageBlocks[$resourceName][$regionName])) {
            return '<!-- ' . sprintf($view->translate('Resource page blocks error: region not supported "%s"'), $regionName) . ' -->';
        }
        $blockMarkup = [];
        foreach ($this->resourcePageBlocks[$resourceName][$regionName] as $blockName) {
            $blockLayout = $this->blockLayoutManager->get($blockName);
            $blockMarkup[] = $blockLayout->render($view, $resource);
        }
        return implode('', $blockMarkup);
    }
}
