<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageRepresentation;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Laminas\View\Helper\AbstractHelper;

class PageLayout extends AbstractHelper
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

        $gridColumns = (int) $page->layoutDataValue('grid_columns');
        $gridColumnGap = (int) $page->layoutDataValue('grid_column_gap', 10);
        $gridRowGap = (int) $page->layoutDataValue('grid_row_gap', 10);

        // Prepare the page layout.
        switch ($page->layout()) {
            case 'grid':
                $view->headLink()->appendStylesheet($view->assetUrl('css/page-grid.css', 'Omeka'));
                $classes[] = 'page-layout-grid';
                $inlineStyles[] = sprintf('grid-template-columns: repeat(%s, 1fr);', $gridColumns);
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
                echo sprintf(
                    '<div class="block-group %1$s" style="display: grid; grid-template-columns: repeat(%2$s, 1fr); grid-column: span %2$s;">',
                    $view->escapeHtml($block->dataValue('class')),
                    $view->escapeHtml($gridColumns)
                );
            } else {
                // Render each block according to page layout.
                switch ($page->layout()) {
                    case 'grid':
                        $blockLayoutData = $block->layoutData();
                        $getValidPosition = fn ($columnPosition) => in_array($columnPosition, ['auto',...range(1, $gridColumns)]) ? $columnPosition : 'auto';
                        $getValidSpan = fn ($columnSpan) => in_array($columnSpan, range(1, $gridColumns)) ? $columnSpan : $gridColumns;
                        echo sprintf(
                            '<div style="grid-column: %s / span %s">%s</div>',
                            $getValidPosition($blockLayoutData['grid_column_position'] ?? 'auto'),
                            $getValidSpan($blockLayoutData['grid_column_span'] ?? $gridColumns),
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
