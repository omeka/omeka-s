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
        echo sprintf(
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
                    echo '</div>'; // Blocks may not overlap.
                }
                $inBlockGroup = true;
                $blockGroupSpan = (int) $block->dataValue('span');
                $blockGroupCurrentSpan = 0;
                $blockGroupClasses = $this->getBlockClasses($block);
                $blockGroupInlineStyles = $this->getBlockInlineStyles($block);
                if ('grid' === $page->layout()) {
                    $blockGroupClasses[] = 'block-group-grid';
                    $blockGroupClasses[] = sprintf('grid-template-columns-%s', $gridColumns);
                    $blockGroupClasses[] = 'grid-position-1';
                    $blockGroupClasses[] = sprintf('grid-span-%s', $gridColumns);
                }
                echo sprintf(
                    '<div class="block-group %s" style="%s">',
                    $view->escapeHtml(implode(' ', $blockGroupClasses)),
                    $view->escapeHtml(implode(' ', $blockGroupInlineStyles))
                );
            } else {
                // Render each block according to page layout.
                switch ($page->layout()) {
                    case 'grid':
                        $blockLayoutData = $block->layoutData();
                        // Get the valid position and span classes, which in CSS map to:
                        //  - grid-column-start: <position>;
                        //  - grid-column-end: span <span>;
                        $getValidPositionClass = fn ($columnPosition) => in_array($columnPosition, ['auto',...range(1, $gridColumns)]) ? sprintf('grid-position-%s', $columnPosition) : 'grid-position-auto';
                        $getValidSpanClass = fn ($columnSpan) => in_array($columnSpan, range(1, $gridColumns)) ? sprintf('grid-span-%s', $columnSpan) : sprintf('grid-span-%s', $gridColumns);
                        echo sprintf(
                            '<div class="%s %s">%s</div>',
                            $getValidPositionClass($blockLayoutData['grid_column_position'] ?? 'grid-position-auto'),
                            $getValidSpanClass($blockLayoutData['grid_column_span'] ?? sprintf('grid-span-%s', $gridColumns)),
                            $view->blockLayout()->render($block)
                        );
                        break;
                    case '':
                    default:
                        echo $view->blockLayout()->render($block);
                        break;
                }
            }
            // The blockGroup block gets special treatment.
            if ($inBlockGroup) {
                if ($blockGroupCurrentSpan == $blockGroupSpan) {
                    echo '</div>';
                    $inBlockGroup = false;
                } else {
                    $blockGroupCurrentSpan++;
                }
            }
        }
        if ($inBlockGroup) {
            echo '</div>'; // Close the blockGroup block if not already closed.
        }
        echo '</div>';
    }
}
