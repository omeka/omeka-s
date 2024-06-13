<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class GlbRenderer extends AbstractRenderer
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $view->headScript()->appendFile(
            'https://ajax.googleapis.com/ajax/libs/model-viewer/3.5.0/model-viewer.min.js',
            'module'
        );
        $modelViewer = $view->htmlElement('model-viewer');
        $modelViewer->setAttributes([
            'src' => $media->originalUrl(),
            'alt' => $media->altText(),
            'camera-controls' => true,
            'style' => 'width: 600px; height: 600px;',
        ]);
        return $modelViewer;
    }
}
