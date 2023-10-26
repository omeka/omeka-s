<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\Manager as BlockLayoutManager;
use Omeka\Site\Theme\Theme;
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
     * @return string
     */
    public function render(SitePageBlockRepresentation $block)
    {
        $view = $this->getView();

        // Allow modules to add classes for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['classes' => []]);
        $this->eventManager->triggerEvent(new Event('block_layout.classes', $block, $eventArgs));
        $classes = $eventArgs['classes'];

        // Allow modules to add inline styles for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['inline_styles' => []]);
        $this->eventManager->triggerEvent(new Event('block_layout.inline_styles', $block, $eventArgs));
        $inlineStyles = $eventArgs['inline_styles'];

        // Add classes and inline styles, if any.
        $class = $block->layoutDataValue('class');
        if (is_string($class) && '' !== trim($class)) {
            $classes[] = $class;
        }
        $alignment = $block->layoutDataValue('alignment');
        switch ($alignment) {
            case 'left':
                $classes[] = 'block-layout-alignment-left';
                break;
            case 'right':
                $classes[] = 'block-layout-alignment-right';
                break;
            case 'center':
                $classes[] = 'block-layout-alignment-center';
                break;
            default:
                // No alignment
        }
        $backgroundImageAsset = $block->layoutDataValue('background_image_asset');
        if ($backgroundImageAsset) {
            $asset = $view->api()->searchOne('assets', ['id' => $backgroundImageAsset])->getContent();
            if ($asset) {
                $inlineStyles[] = sprintf('background-image: url("%s");', $view->escapeCss($asset->assetUrl()));
            }
        }
        $backgroundPositionY = $block->layoutDataValue('background_position_y');
        if ($backgroundPositionY) {
            switch ($backgroundPositionY) {
                case 'top':
                    $classes[] = 'block-layout-background-position-y-top';
                    break;
                case 'center':
                    $classes[] = 'block-layout-background-position-y-center';
                    break;
                case 'bottom':
                    $classes[] = 'block-layout-background-position-y-bottom';
                    break;
                default:
                    // No background position Y
            }
        }
        $backgroundPositionX = $block->layoutDataValue('background_position_x');
        if ($backgroundPositionX) {
            switch ($backgroundPositionX) {
                case 'left':
                    $classes[] = 'block-layout-background-position-x-left';
                    break;
                case 'center':
                    $classes[] = 'block-layout-background-position-x-center';
                    break;
                case 'right':
                    $classes[] = 'block-layout-background-position-x-right';
                    break;
                default:
                    // No background position X
            }
        }

        $view = $this->getView();
        $blockLayout = $this->manager->get($block->layout());

        // Set the configured block template, if any.
        $templateName = $block->layoutDataValue('template_name');
        $templateViewScript = null;
        if ($templateName) {
            // Verify that the current theme provides this template.
            $config = $this->currentTheme->getConfigSpec();
            if (isset($config['block_templates'][$block->layout()][$templateName])) {
                $templateViewScript = sprintf('common/block-template/%s', $templateName);
            }
        }

        // Wrap block markup in a div only if the layout declares special
        // styling via classes or inline styles.
        if ($classes || $inlineStyles) {
            return sprintf(
                '<div class="%s" style="%s">%s</div>',
                $view->escapeHtml(implode(' ', $classes)),
                $view->escapeHtml(implode(' ', $inlineStyles)),
                $templateViewScript
                    ? $blockLayout->render($this->getView(), $block, $templateViewScript)
                    : $blockLayout->render($this->getView(), $block)
            );
        }

        return $templateViewScript
            ? $blockLayout->render($this->getView(), $block, $templateViewScript)
            : $blockLayout->render($this->getView(), $block);
    }
}
