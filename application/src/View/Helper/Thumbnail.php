<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\AbstractRepresentation;
use Zend\View\Helper\AbstractHtmlElement;

class Thumbnail extends AbstractHtmlElement
{
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
