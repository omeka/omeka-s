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
        $backgroundSize = $block->layoutDataValue('background_size');
        if ($backgroundSize) {
            switch ($backgroundSize) {
                case 'cover':
                    $classes[] = 'block-layout-background-size-cover';
                    break;
                case 'contain':
                    $classes[] = 'block-layout-background-size-contain';
                    break;
                default:
                    // No background size
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
