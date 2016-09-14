<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

interface BlockLayoutInterface
{
    /**
     * Get a human-readable label for the block layout.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Prepare the view to enable the block layout form.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function prepareForm(PhpRenderer $view);

    /**
     * Prepare the view to enable the block layout render.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function prepareRender(PhpRenderer $view);

    /**
     * Process and validate block data.
     *
     * @param SitePageBlock $block
     * @param ErrorStore $errorStore
     */
    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore);

    /**
     * Render a form for adding/editing a block.
     *
     * @param PhpRenderer $view
     * @param SiteRepresentation $site
     * @param SitePageRepresentation $page
     * @param null|SitePageBlockRepresentation $block
     * @return string
     */
    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null);

    /**
     * Render the provided block.
     *
     * @param PhpRenderer $view
     * @param SitePageBlockRepresentation $block
     * @return string
     */
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block);
}
