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

        // Allow modules to add inline styles for styling the layout.
        $eventArgs = $this->eventManager->prepareArgs(['inline_styles' => []]);
        $this->eventManager->triggerEvent(new Event('block_layout.inline_styles', $block, $eventArgs));
        $inlineStyles = $eventArgs['inline_styles'];

        $backgroundImageAsset = $block->layoutDataValue('background_image_asset');
        if ($backgroundImageAsset) {
            $asset = $view->api()->searchOne('assets', ['id' => $backgroundImageAsset])->getContent();
            if ($asset) {
                $inlineStyles[] = sprintf('background-image: url("%s");', $view->escapeCss($asset->assetUrl()));
            }
        }
        $maxWidth = $block->layoutDataValue('max_width');
        if (is_string($maxWidth) && preg_match(sprintf('/%s/', LengthCssDataType::PATTERN), $maxWidth)) {
            $inlineStyles[] = sprintf('max-width: %s;', $maxWidth);
        }
        $minHeight = $block->layoutDataValue('min_height');
        if (is_string($minHeight) && preg_match(sprintf('/%s/', LengthCssDataType::PATTERN), $minHeight)) {
            $inlineStyles[] = sprintf('min-height: %s;', $minHeight);
        }

        return $inlineStyles;
    }
}
