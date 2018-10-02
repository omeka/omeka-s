<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\AbstractRepresentation;
use Zend\View\Helper\AbstractHtmlElement;

/**
 * View helper for rendering a thumbnail image.
 */
class Thumbnail extends AbstractHtmlElement
{
    /**
     * Render a thumbnail image.
     *
     * @param AbstractRepresentation $representation
     * @param string $type
     * @param array $attribs
     */
    public function __invoke(AbstractRepresentation $representation, $type, array $attribs = [])
    {
        $thumbnail = $representation->thumbnail();
        $primaryMedia = $representation->primaryMedia();
        if (!$thumbnail && !$primaryMedia) {
            return '';
        }

        $attribs['src'] = $thumbnail ? $thumbnail->assetUrl() : $primaryMedia->thumbnailUrl($type);

        // Trigger attribs event
        $triggerHelper = $this->getView()->plugin('trigger');
        $params = compact('attribs', 'thumbnail', 'primaryMedia', 'representation', 'type');
        $params = $triggerHelper('view_helper.thumbnail.attribs', $params, true);
        $attribs = $params['attribs'];

        if (!isset($attribs['alt'])) {
            $attribs['alt'] = '';
        }

        return sprintf('<img%s>', $this->htmlAttribs($attribs));
    }
}
