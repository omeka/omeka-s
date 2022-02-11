<?php
namespace Omeka\Site\ResourcePageBlockLayout;

interface ResourcePageBlockLayoutInterface
{
    /**
     * Get a human-readable label for the block layout.
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
     * Render the block.
     *
     * @param PhpRenderer $view
     * @return string
     */
    public function render(PhpRenderer $view) : string;
}
