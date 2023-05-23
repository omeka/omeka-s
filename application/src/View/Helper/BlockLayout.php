<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\Manager as BlockLayoutManager;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Laminas\View\Helper\AbstractHelper;

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

    protected $eventManager;

    public function __construct(BlockLayoutManager $manager, EventManager $eventManager)
    {
        $this->manager = $manager;
        $this->eventManager = $eventManager;
    }

    /**
     * Get all registered layouts.
     *
     * @return array
     */
    public function getLayouts()
    {
        return $this->manager->getRegisteredNames(true);
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
        $layoutData = [];
        if ($layout instanceof SitePageBlockRepresentation) {
            $block = $layout;
            $layout = $block->layout();
            $page = $block->page();
            $site = $page->site();
            $layoutData = $block->layoutData();
        }
        $partialName = $partialName ?: self::PARTIAL_NAME;
        return $view->partial(
            $partialName,
            [
                'layout' => $layout,
                'layoutLabel' => $this->getLayoutLabel($layout),
                'layoutData' => $layoutData,
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
        // Allow modules to add classes for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['classes' => []]);
        $this->eventManager->triggerEvent(new Event('block_layout.classes', $block, $eventArgs));
        $classes = $eventArgs['classes'];

        // Allow modules to add inline styles for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['inline_styles' => []]);
        $this->eventManager->triggerEvent(new Event('block_layout.inline_styles', $block, $eventArgs));
        $inlineStyles = $eventArgs['inline_styles'];

        // Add classes and inline styles, if any.
        $layoutData = $block->layoutData();
        if (isset($layoutData['class']) && is_string($layoutData['class']) && '' !== trim($layoutData['class'])) {
            $classes[] = $layoutData['class'];
        }

        $view = $this->getView();
        $blockLayout = $this->manager->get($block->layout());

        // Wrap block markup in a div only if the layout declares special
        // styling via classes or inline styles.
        if ($classes || $inlineStyles) {
            return sprintf(
                '<div class="%s" style="%s">%s</div>',
                $view->escapeHtml(implode(' ', $classes)),
                $view->escapeHtml(implode(' ', $inlineStyles)),
                $blockLayout->render($this->getView(), $block)
            );
        }

        return $blockLayout->render($this->getView(), $block);
    }
}
