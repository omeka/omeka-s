<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class Fallback implements ResourcePageBlockLayoutInterface
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getLabel() : string
    {
        return sprintf('Unknown [%s]', $this->name); // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return '';
    }
}
