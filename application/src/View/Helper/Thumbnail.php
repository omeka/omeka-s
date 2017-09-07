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
        $primaryMedia = $representation->primaryMedia();
        if (!$primaryMedia) {
            return '';
        }

        $attribs['src'] = $primaryMedia->thumbnailUrl($type);
        if (!isset($attribs['alt'])) {
            $attribs['alt'] = '';
        }

        return sprintf('<img %s>', $this->htmlAttribs($attribs));
    }
}
