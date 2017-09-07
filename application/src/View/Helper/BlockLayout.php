<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\Manager as BlockLayoutManager;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering block layouts.
 */
class BlockLayout extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout';

    /**
     * @var BlockLayoutManager
     */
    protected $manager;

    public function __construct(BlockLayoutManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get all registered layouts.
     *
     * @return array
     */
    public function getLayouts()
    {
        return $this->manager->getRegisteredNames();
    }

    /**
     * Get a layout label.
     *
     * @param string $layout
     * @return string
     */
    public function getLayoutLabel($layout)
    {
        return $this->manager->get($layout)->getLabel();
    }

    /**
     * Prepare the view to enable the block layout form.
     */
    public function prepareForm()
    {
        foreach ($this->getLayouts() as $layout) {
            $this->manager->get($layout)->prepareForm($this->getView());
        }
    }

    /**
     * Return the HTML necessary to render all block forms.
     *
     * @param SitePageRepresentation $sitePage
     */
    public function forms(SitePageRepresentation $sitePage)
    {
        $html = '<div id="blocks">';
        foreach ($sitePage->blocks() as $block) {
            $html .= $this->form($block);
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Return the HTML necessary to render a block form.
     *
     * @param string|SitePageBlockRepresentation $layout The layout for add or
     *   a block representation for edit
     * @param null|SiteRepresentation $site This layout/block's site
     * @return string
     */
    public function form($layout, SiteRepresentation $site = null,
        SitePageRepresentation $page = null, $partialName = null
    ) {
        $view = $this->getView();
        $block = null;
        if ($layout instanceof SitePageBlockRepresentation) {
            $block = $layout;
            $layout = $block->layout();
            $page = $block->page();
            $site = $page->site();
        }
        $partialName = $partialName ?: self::PARTIAL_NAME;
        return $view->partial(
            $partialName,
            [
                'layout' => $layout,
                'layoutLabel' => $this->getLayoutLabel($layout),
                'blockContent' => $this->manager->get($layout)->form($this->getView(), $site, $page, $block),
            ]
        );
    }

    /**
     * Prepare the view to enable the block layout.
     *
     * @param string $layout
     */
    public function prepareRender($layout)
    {
        $this->manager->get($layout)->prepareRender($this->getView());
    }

    /**
     * Return the HTML necessary to render the provided block.
     *
     * @param SitePageBlockRepresentation $block
     * @return string
     */
    public function render(SitePageBlockRepresentation $block)
    {
        return $this->manager->get($block->layout())->render($this->getView(), $block);
    }
}
