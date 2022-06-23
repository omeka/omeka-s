<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $iiifViewerUrl = $view->url('iiif-viewer', [], ['force_canonical' => true, 'query' => ['url' => $media->source()]]);
        return sprintf(
            '<iframe style="width: 100%%; height: 700px;" src="%s"></iframe>%s',
            $view->escapeHtml($iiifViewerUrl),
            $view->hyperlink($view->translate('Full view'), $iiifViewerUrl, ['target' => '_blank'])
        );
    }
}
