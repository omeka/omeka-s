<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Renderer\PhpRenderer;

interface ResourcePageBlockLayoutInterface
{
    /**
     * Get a human-readable label for this block layout.
     *
     * @return string
     */
    public function getLabel() : string;

    /**
     * Get the names of resources that are compatible with this block layout.
     *
     * @return array
     */
    public function getCompatibleResourceNames() : array;

    /**
     * Return the markup for this block layout.
     *
     * @param PhpRenderer $view
     * @param AbstractResourceEntityRepresentation $resource
     * @return string
     */
    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string;
}
