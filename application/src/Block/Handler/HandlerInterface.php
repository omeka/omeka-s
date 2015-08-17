<?php
namespace Omeka\Block\Handler;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for site page block handlers.
 *
 * Each handler corresponds to one block layout.
 */
interface HandlerInterface
{
    /**
     * Get a human-readable label for the block layout.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Render a form for adding/editing a block.
     *
     * @param PhpRenderer $view
     * @param int $index The block index on the form
     * @param SitePageBlockRepresentation $block
     * @return string
     */
    public function form(PhpRenderer $view, $index, SitePageBlockRepresentation $block = null);

    /**
     * Render the provided block.
     *
     * @param PhpRenderer $view
     * @param SitePageBlockRepresentation $block
     * @return string
     */
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block);
}
