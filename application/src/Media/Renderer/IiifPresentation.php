<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $iiifViewerUrl = $view->url('iiif-viewer', [], ['force_canonical' => true, 'query' => ['url' => $media->source()]]);
        $width = '100%';
        if (isset($options['width']) && is_numeric($options['width'])) {
            $width = sprintf('%spx', $options['width']);
        }
        $height = '700px';
        if (isset($options['height']) && is_numeric($options['height'])) {
            $height = sprintf('%spx', $options['height']);
        }
        return sprintf(
            '<iframe style="width: %s; height: %s;" src="%s"></iframe>%s',
            $view->escapeHtml($width),
            $view->escapeHtml($height),
            $view->escapeHtml($iiifViewerUrl),
            $view->hyperlink($view->translate('Full view'), $iiifViewerUrl, ['target' => '_blank'])
        );
    }
}
