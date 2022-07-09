<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $query = [
            'url' => $media->source(),
            'mirador_config' => json_encode([
                'window.sideBarOpen' => false,
            ]),
        ];
        $iiifViewerUrl = $view->url('iiif-viewer', [], ['force_canonical' => true, 'query' => $query]);
        $width = '100%';
        if (isset($options['width'])) {
            $width = sprintf('%spx', (int) $options['width']);
        }
        $height = '700px';
        if (isset($options['height'])) {
            $height = sprintf('%spx', (int) $options['height']);
        }
        return sprintf(
            '<iframe style="width: %s; height: %s;" src="%s"></iframe>',
            $width,
            $height,
            $view->escapeHtml($iiifViewerUrl)
        );
    }
}
