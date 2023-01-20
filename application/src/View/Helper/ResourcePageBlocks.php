<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation;
use Omeka\Site\ResourcePageBlockLayout\Manager;
use Laminas\View\Helper\AbstractHelper;

class ResourcePageBlocks extends AbstractHelper
{
    protected $resource;

    protected $regionName;

    protected $resourceName;

    protected $blockLayoutManager;

    protected $resourcePageBlocks;

    public function __construct(Manager $blockLayoutManager, array $resourcePageBlocks)
    {
        $this->blockLayoutManager = $blockLayoutManager;
        $this->resourcePageBlocks = $resourcePageBlocks;
    }

    /**
     * Set the resource/region and return this object.
     *
     * @param Representation\AbstractResourceEntityRepresentation $resource
     * @param string $regionName
     * @return self
     */
    public function __invoke(Representation\AbstractResourceEntityRepresentation $resource, $regionName = 'main')
    {
        $this->resource = $resource;
        $this->regionName = $regionName;

        $resourceClass = get_class($resource);
        switch ($resourceClass) {
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
                throw new \Exception('Invalid resource');
        }
        return $this;
    }

    /**
     * Does this resource/region have blocks?
     *
     * @return bool
     */
    public function hasBlocks()
    {
        return isset($this->resourcePageBlocks[$this->resourceName][$this->regionName]);
    }

    /**
     * Return the block markup for a region of the resource page.
     *
     * @return string
     */
    public function getBlocks()
    {
        if (!$this->hasBlocks()) {
            return '';
        }
        $view = $this->getView();
        $blockMarkup = [];
        foreach ($this->resourcePageBlocks[$this->resourceName][$this->regionName] as $blockName) {
            $blockLayout = $this->blockLayoutManager->get($blockName);
            $blockMarkup[] = $blockLayout->render($view, $this->resource);
        }
        return implode('', $blockMarkup);
    }
}
