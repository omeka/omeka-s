<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageRepresentation;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;

class PageLayout extends AbstractLayout
{
    protected $eventManager;

    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function render(SitePageRepresentation $page)
    {
        $view = $this->getView();
        $output = [];

        // Allow modules to add classes for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['classes' => []]);
        $this->eventManager->triggerEvent(new Event('page_layout.classes', $page, $eventArgs));
        $classes = $eventArgs['classes'];

        // Allow modules to add inline styles for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['inline_styles' => []]);
        $this->eventManager->triggerEvent(new Event('page_layout.inline_styles', $page, $eventArgs));
        $inlineStyles = $eventArgs['inline_styles'];

        // Prepare the page layout.
        switch ($page->layout()) {
            case 'grid':
                $view->headLink()->appendStylesheet($view->assetUrl('css/page-grid.css', 'Omeka'));

                $gridColumns = (int) $page->layoutDataValue('grid_columns');
                $gridColumnGap = (int) $page->layoutDataValue('grid_column_gap', 10);
                $gridRowGap = (int) $page->layoutDataValue('grid_row_gap', 10);

                $classes[] = 'page-layout-grid';
                $classes[] = sprintf('grid-template-columns-%s', $gridColumns);
                $inlineStyles[] = sprintf('column-gap: %spx;', $gridColumnGap);
                $inlineStyles[] = sprintf('row-gap: %spx;', $gridRowGap);
                break;
            case '':
            default:
                $classes[] = 'page-layout-normal';
                break;
        }
        $output[] = sprintf(
            '<div class="blocks-inner %s" style="%s">',
            $view->escapeHtml(implode(' ', $classes)),
            $view->escapeHtml(implode(' ', $inlineStyles))
        );
        $layouts = [];
        $inBlockGroup = false;
        foreach ($page->blocks() as $block) {
            if (!array_key_exists($block->layout(), $layouts)) {
                // Prepare render only once per block layout type.
                $layouts[$block->layout()] = null;
                $view->blockLayout()->prepareRender($block->layout());
            }
            if ('blockGroup' === $block->layout()) {
                // The blockGroup block gets special treatment.
                if ($inBlockGroup) {
                    $output[] = '</div>'; // Blocks may not overlap.
                }
                $inBlockGroup = true;
                $blockGroupSpan = (int) $block->dataValue('span');
                $blockGroupCurrentSpan = 0;
                $blockGroupClasses = $this->getBlockClasses($block);
                $blockGroupInlineStyles = $this->getBlockInlineStyles($block);
                if ('grid' === $page->layout()) {
                    $blockGroupClasses[] = 'block-group-grid';
                    $blockGroupClasses[] = 'grid-position-1';
                    $blockGroupClasses[] = sprintf('grid-span-%s', $gridColumns);
                }
                $output[] = sprintf(
                    '<div class="%s" style="%s">',
                    $view->escapeHtml(implode(' ', $blockGroupClasses)),
                    $view->escapeHtml(implode('; ', $blockGroupInlineStyles))
                );
            } else {
                $output[] = $view->blockLayout()->render($block);
            }
            // The blockGroup block gets special treatment.
            if ($inBlockGroup) {
                if ($blockGroupCurrentSpan == $blockGroupSpan) {
                    $output[] = '</div>';
                    $inBlockGroup = false;
                } else {
                    $blockGroupCurrentSpan++;
                }
            }
        }
        if ($inBlockGroup) {
            $output[] = '</div>'; // Close the blockGroup block if not already closed.
        }
        $output[] = '</div>';
        return implode('', $output);
    }
}
