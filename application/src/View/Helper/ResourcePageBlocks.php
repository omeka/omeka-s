<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation;
use Omeka\Site\ResourcePageBlockLayout\Manager;
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
     * @return string
     */
    public function __invoke(Representation\AbstractResourceEntityRepresentation $resource, $regionName = 'main')
    {
        if (!$this->hasBlocks($resource, $regionName)) {
            return '';
        }
        $view = $this->getView();
        $resourceName = $this->getResourceName($resource);
        $blockMarkup = [];
        foreach ($this->resourcePageBlocks[$resourceName][$regionName] as $blockName) {
            $blockLayout = $this->blockLayoutManager->get($blockName);
            $blockMarkup[] = $blockLayout->render($view, $resource);
        }
        return implode('', $blockMarkup);
    }

    /**
     * Does this resource/region have blocks?
     *
     * @param Representation\AbstractResourceEntityRepresentation $resource
     * @param string $regionName
     * @return bool
     */
    public function hasBlocks(Representation\AbstractResourceEntityRepresentation $resource, $regionName = 'main')
    {
        $resourceName = $this->getResourceName($resource);
        return isset($this->resourcePageBlocks[$resourceName][$regionName]);
    }

    /**
     * Get the resource name of the passed resource.
     *
     * @param Representation\AbstractResourceEntityRepresentation $resource
     * @return string
     */
    protected function getResourceName(Representation\AbstractResourceEntityRepresentation $resource)
    {
        $resourceClass = get_class($resource);
        switch ($resourceClass) {
            case Representation\ItemRepresentation::class:
                return 'items';
            case Representation\ItemSetRepresentation::class:
                return 'item_sets';
            case Representation\MediaRepresentation::class:
                return 'media';
            default:
                throw new \Exception('Invalid resource');
        }
    }
}
