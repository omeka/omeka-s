<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\Manager as BlockLayoutManager;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;
use Omeka\Site\Theme\Theme;
use Laminas\EventManager\EventManager;

/**
 * View helper for rendering block layouts.
 */
class BlockLayout extends AbstractLayout
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

    protected $currentTheme;

    public function __construct(BlockLayoutManager $manager, EventManager $eventManager, ?Theme $currentTheme)
    {
        $this->manager = $manager;
        $this->eventManager = $eventManager;
        $this->currentTheme = $currentTheme;
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
     * @param ?string $templateViewScript
     * @return string
     */
    public function render(SitePageBlockRepresentation $block, string $templateViewScript = null)
    {
        $view = $this->getView();
        $blockLayout = $this->manager->get($block->layout());

        // Set the configured block template, if any.
        $templateName = $block->layoutDataValue('template_name');
        if ($templateName && $blockLayout instanceof TemplateableBlockLayoutInterface) {
            // Verify that the current theme provides this template.
            $config = $this->currentTheme->getConfigSpec();
            if (isset($config['block_templates'][$block->layout()][$templateName])) {
                $templateViewScript = sprintf('common/block-template/%s', $templateName);
            }
        }

        $classes = $this->getBlockClasses($block);
        $inlineStyles = $this->getBlockInlineStyles($block);

        return sprintf(
            '<div class="%s" style="%s">%s</div>',
            $view->escapeHtml(implode(' ', $classes)),
            $view->escapeHtml(implode('; ', $inlineStyles)),
            $templateViewScript
                ? $blockLayout->render($this->getView(), $block, $templateViewScript)
                : $blockLayout->render($this->getView(), $block)
        );
    }
}
