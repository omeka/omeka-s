<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

class OEmbed implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        $data = $media->mediaData();

        if ($data['type'] == 'photo') {
            $url = $data['url'];
            $width = $data['width'];
            $height = $data['height'];
            if (!empty($data['title'])) {
                $title = $data['title'];
            } else {
                $title = $url;
            }
            return sprintf(
                '<img src="%s" width="%s" height="%s" alt="%s">',
                $view->escapeHtml($url),
                $view->escapeHtml($width),
                $view->escapeHtml($height),
                $view->escapeHtml($title)
            );
        } elseif (!empty($data['html'])) {
            return $data['html'];
        } else {
            $source = $media->source();
            if (!$empty($data['title'])) {
                $title = $data['title'];
            } else {
                $title = $source;
            }
            return $view->hyperlink($title, $source);
        }
    }
}
