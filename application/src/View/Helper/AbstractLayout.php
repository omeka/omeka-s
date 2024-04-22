<?php
namespace Omeka\View\Helper;

use Laminas\EventManager\Event;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Form\Element\LengthCssDataType;

abstract class AbstractLayout extends AbstractHelper
{
    /**
     * Get classes for a block div.
     *
     * @param SitePageBlockRepresentation $block
     * @return array
     */
    public function getBlockClasses(SitePageBlockRepresentation $block)
    {
        // Allow modules to add classes for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['classes' => []]);
        $this->eventManager->triggerEvent(new Event('block_layout.classes', $block, $eventArgs));
        $classes = $eventArgs['classes'];

        $classes[] = 'block';
        $classes[] = sprintf('block-%s', $block->layout());

        $page = $block->page();
        if ('grid' === $page->layout() && 'blockGroup' !== $block->layout()) {
            // Note that blockGroup position and span is set in PageLayout::render().
            $gridColumns = (int) $page->layoutDataValue('grid_columns');
            // Get the valid position and span classes, which in CSS map to:
            //  - grid-column-start: <position>;
            //  - grid-column-end: span <span>;
            $getValidPositionClass = fn ($columnPosition) => in_array($columnPosition, ['auto',...range(1, $gridColumns)]) ? sprintf('grid-position-%s', $columnPosition) : 'grid-position-auto';
            $getValidSpanClass = fn ($columnSpan) => in_array($columnSpan, range(1, $gridColumns)) ? sprintf('grid-span-%s', $columnSpan) : sprintf('grid-span-%s', $gridColumns);
            $classes[] = $getValidPositionClass($block->layoutDataValue('grid_column_position') ?? 'grid-position-auto');
            $classes[] = $getValidSpanClass($block->layoutDataValue('grid_column_span') ?? sprintf('grid-span-%s', $gridColumns));
        }

        $class = $block->layoutDataValue('class');
        if (is_string($class) && '' !== trim($class)) {
            $classes[] = $class;
        }
        $alignmentBlock = $block->layoutDataValue('alignment_block');
        switch ($alignmentBlock) {
            case 'left':
                $classes[] = 'block-layout-alignment-block-left';
                break;
            case 'right':
                $classes[] = 'block-layout-alignment-block-right';
                break;
            case 'center':
                $classes[] = 'block-layout-alignment-block-center';
                break;
            default:
                // No block alignment
        }
        $alignmentText = $block->layoutDataValue('alignment_text');
        switch ($alignmentText) {
            case 'left':
                $classes[] = 'block-layout-alignment-text-left';
                break;
            case 'center':
                $classes[] = 'block-layout-alignment-text-center';
                break;
            case 'right':
                $classes[] = 'block-layout-alignment-text-right';
                break;
            case 'justify':
                $classes[] = 'block-layout-alignment-text-justify';
                break;
            default:
                // No text alignment
        }
        $backgroundImage = $block->layoutDataValue('background_image_asset');
        $backgroundColor = $block->layoutDataValue('background_color');
        if ($backgroundImage || $backgroundColor) {
            $classes[] = 'has-background';
        }
        $backgroundImagePositionY = $block->layoutDataValue('background_image_position_y');
        if ($backgroundImagePositionY) {
            switch ($backgroundImagePositionY) {
                case 'top':
                    $classes[] = 'block-layout-background-image-position-y-top';
                    break;
                case 'center':
                    $classes[] = 'block-layout-background-image-position-y-center';
                    break;
                case 'bottom':
                    $classes[] = 'block-layout-background-image-position-y-bottom';
                    break;
                default:
                    // No background image position Y
            }
        }
        $backgroundImagePositionX = $block->layoutDataValue('background_image_position_x');
        if ($backgroundImagePositionX) {
            switch ($backgroundImagePositionX) {
                case 'left':
                    $classes[] = 'block-layout-background-image-position-x-left';
                    break;
                case 'center':
                    $classes[] = 'block-layout-background-image-position-x-center';
                    break;
                case 'right':
                    $classes[] = 'block-layout-background-image-position-x-right';
                    break;
                default:
                    // No background image position X
            }
        }
        $backgroundImageSize = $block->layoutDataValue('background_image_size');
        if ($backgroundImageSize) {
            switch ($backgroundImageSize) {
                case 'cover':
                    $classes[] = 'block-layout-background-image-size-cover';
                    break;
                case 'contain':
                    $classes[] = 'block-layout-background-image-size-contain';
                    break;
                default:
                    // No background image size
            }
        }

        return $classes;
    }

    /**
     * Get inline styles for a block div.
     *
     * @param SitePageBlockRepresentation $block
     * @return array
     */
    public function getBlockInlineStyles(SitePageBlockRepresentation $block)
    {
        $view = $this->getView();

        // Validate a CSS <hex-color>.
        $isValidHexColor = fn ($hexColor) => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $hexColor);
        // Validate a CSS <length>
        $isValidLength = fn ($length) => preg_match(sprintf('/%s/', LengthCssDataType::PATTERN), $length);
        // Prepare a CSS <length> for use in an inline style. Note that we convert bare numbers as pixels.
        $prepareLength = fn ($length) => is_numeric($length) ? sprintf('%spx', $length) : $length;

        // Allow modules to add inline styles for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['inline_styles' => []]);
        $this->eventManager->triggerEvent(new Event('block_layout.inline_styles', $block, $eventArgs));
        $inlineStyles = $eventArgs['inline_styles'];

        $backgroundColor = $block->layoutDataValue('background_color');
        if ($backgroundColor && $isValidHexColor($backgroundColor)) {
            $inlineStyles[] = sprintf('background-color: %s', $backgroundColor);
        }

        $backgroundImageAsset = $block->layoutDataValue('background_image_asset');
        if ($backgroundImageAsset) {
            $asset = $view->api()->searchOne('assets', ['id' => $backgroundImageAsset])->getContent();
            if ($asset) {
                $inlineStyles[] = sprintf('background-image: url("%s")', $view->escapeCss($asset->assetUrl()));
            }
        }

        $maxWidth = $block->layoutDataValue('max_width');
        if (is_string($maxWidth) && $isValidLength($maxWidth)) {
            $inlineStyles[] = sprintf('max-width: %s', $prepareLength($maxWidth));
        }
        $minHeight = $block->layoutDataValue('min_height');
        if (is_string($minHeight) && $isValidLength($minHeight)) {
            $inlineStyles[] = sprintf('min-height: %s', $prepareLength($minHeight));
        }

        $paddingTop = $block->layoutDataValue('padding_top');
        if (is_string($paddingTop) && $isValidLength($paddingTop)) {
            $inlineStyles[] = sprintf('padding-top: %s', $prepareLength($paddingTop));
        }
        $paddingRight = $block->layoutDataValue('padding_right');
        if (is_string($paddingRight) && $isValidLength($paddingRight)) {
            $inlineStyles[] = sprintf('padding-right: %s', $prepareLength($paddingRight));
        }
        $paddingBottom = $block->layoutDataValue('padding_bottom');
        if (is_string($paddingBottom) && $isValidLength($paddingBottom)) {
            $inlineStyles[] = sprintf('padding-bottom: %s', $prepareLength($paddingBottom));
        }
        $paddingLeft = $block->layoutDataValue('padding_left');
        if (is_string($paddingLeft) && $isValidLength($paddingLeft)) {
            $inlineStyles[] = sprintf('padding-left: %s', $prepareLength($paddingLeft));
        }

        return $inlineStyles;
    }
}
