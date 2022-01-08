<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class ThumbnailRenderer implements RendererInterface
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

        $title = $media->displayTitle();

        return sprintf('<a href="%s" title="%s">%s</a>', $view->escapeHtml($url), $view->escapeHtml($title), $img);
    }

    /**
     * Get the URL for the given type of link
     *
     * @param MediaRepresentation $media
     * @param string $linkType Type of link: 'original', 'item', and 'media' are valid
     * @throws \InvalidArgumentException On unrecognized $linkType
     * @return string
     */
    protected function getLinkUrl(MediaRepresentation $media, $linkType)
    {
        switch ($linkType) {
            case 'original':
                return $media->originalUrl();
            case 'item':
                return $media->item()->url();
            case 'media':
                return $media->url();
            default:
                throw new \InvalidArgumentException(sprintf('Invalid link type "%s"', $linkType));
        }
    }
}
