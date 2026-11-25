<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\AbstractRepresentation;
use Laminas\View\Helper\AbstractHtmlElement;

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
        $url = $representation->thumbnailDisplayUrl($type);
        if ($url === null) {
            return '';
        }

        $attribs['src'] = $url;

        // Trigger attribs event
        $triggerHelper = $this->getView()->plugin('trigger');
        $params = compact('attribs', 'representation', 'type');
        $params = $triggerHelper('view_helper.thumbnail.attribs', $params, true);
        $attribs = $params['attribs'];

        // Include element for lazy loading. See https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/loading
        if (!isset($attribs['loading'])) {
            // Due to a bug in firefox, the attribute "loading" should be set
            // before src (see https://bugzilla.mozilla.org/show_bug.cgi?id=1647077).
            $attribs = ['loading' => 'lazy'] + $attribs;
        }

        if (!isset($attribs['alt'])) {
            $attribs['alt'] = $representation->thumbnailAltText();
        }

        return sprintf('<img%s>', $this->htmlAttribs($attribs));
    }
}
