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

        // Prepare the page layout.
        switch ($page->layout()) {
            case 'grid':
                $view->headLink()->appendStylesheet($view->assetUrl('css/page-grid.css', 'Omeka'));
                $classes[] = 'page-layout-grid';
                $inlineStyles[] = sprintf(
                    'grid-template-columns: repeat(%s, 1fr);',
                    (int) $page->layoutDataValue('grid_columns')
                );
                $inlineStyles[] = sprintf(
                    'column-gap: %spx;',
                    (int) $page->layoutDataValue('grid_column_gap', 10)
                );
                $inlineStyles[] = sprintf(
                    'row-gap: %spx;',
                    (int) $page->layoutDataValue('grid_row_gap', 10)
                );
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
        foreach ($page->blocks() as $block) {
            if (!array_key_exists($block->layout(), $layouts)) {
                // Prepare render only once per block layout type.
                $layouts[$block->layout()] = null;
                $view->blockLayout()->prepareRender($block->layout());
            }
            // Render each block according to page layout.
            switch ($page->layout()) {
                case 'grid':
                    $gridColumns = (int) $page->layoutDataValue('grid_columns');
                    $blockLayoutData = $block->layoutData();
                    $getValidPosition = fn($columnPosition) => in_array($columnPosition, ['auto',...range(1, $gridColumns)]) ? $columnPosition : 'auto';
                    $getValidSpan = fn($columnSpan) => in_array($columnSpan, range(1, $gridColumns)) ? $columnSpan : $gridColumns;
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
        echo '</div>';

    }
}
