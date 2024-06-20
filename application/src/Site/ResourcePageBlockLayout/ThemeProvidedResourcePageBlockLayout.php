<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class ThemeProvidedResourcePageBlockLayout implements ResourcePageBlockLayoutInterface
{
    protected $label;
    protected $compatibleResourceNames;
    protected $partial;

    public function __construct($label, $compatibleResourceNames, $partial)
    {
        $this->label = $label;
        $this->compatibleResourceNames = $compatibleResourceNames;
        $this->partial = $partial;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function getCompatibleResourceNames() : array
    {
        return $this->compatibleResourceNames;
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial(sprintf('common/resource-page-block-layout/%s', $this->partial), ['resource' => $resource]);
    }
}
