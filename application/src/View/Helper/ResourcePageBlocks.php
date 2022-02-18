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
     * Invoke this helper.
     *
     * @param Representation\AbstractResourceEntityRepresentation $resource
     * @return ResourcePageBlocks
     */
    public function __invoke(Representation\AbstractResourceEntityRepresentation $resource)
    {
        $this->resource = $resource;
        switch (get_class($resource)) {
            case Representation\ItemRepresentation::class:
                $this->resourceName = 'items';
                break;
            case Representation\ItemSetRepresentation::class:
                $this->resourceName = 'item_sets';
                break;
            case Representation\MediaRepresentation::class:
                $this->resourceName = 'media';
                break;
            default:
                throw new \Exception('Cannot invoke resourcePageBlocks(). Invalid resource.');
        }
        return $this;
    }

    /**
     * Return the markup for the "main" region of the resource page.
     *
     * @return string
     */
    public function main()
    {
        $blockMarkup = [];
        foreach ($this->resourcePageBlocks[$this->resourceName]['main'] as $blockName) {
            $blockLayout = $this->blockLayoutManager->get($blockName);
            $blockMarkup[] = $blockLayout->render($this->getView(), $this->resource);
        }
        return implode('', $blockMarkup);
    }
}
