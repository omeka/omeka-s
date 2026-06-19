<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class ThumbnailRenderer extends AbstractRenderer
{
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        $thumbnailType = $options['thumbnailType'] ?? 'large';
        $link = array_key_exists('link', $options) ? $options['link'] : 'original';
        $attribs = $options['thumbnailAttribs'] ?? [];
        $img = $view->thumbnail($media, $thumbnailType, $attribs);
        if (!$link) {
            return $img;
        }

        $url = $this->getLinkUrl($media, $link);
        if (!$url) {
            return $img;
        }

        $linkAttribs = $options['linkAttribs'] ?? [];
        if (!array_key_exists('title', $linkAttribs)) {
            $linkAttribs['title'] = $media->displayTitle();
        }
        return $view->plugin('hyperlink')->raw($img, $url, $linkAttribs);
    }
}
