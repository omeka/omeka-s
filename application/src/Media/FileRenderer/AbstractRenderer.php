<?php
namespace Omeka\Media\FileRenderer;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\MediaRepresentation;

abstract class AbstractRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $link = $options['link'] ?? 'original';
        return $view->hyperlink($media->filename(), $this->getLinkUrl($media, $link));
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
