<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IiifPresentation implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $view->headScript()->appendFile($view->assetUrl('vendor/mirador/mirador.min.js', 'Omeka'));
        $html = <<<'EOD'
        <div id="%1$s" data-iiif-url="%2$s"></div>
        <script type="text/javascript">
        Mirador.viewer({
            id: '%1$s',
            workspaceControlPanel: {
                enabled: false
            },
            windows: [
                {
                    manifestId:  document.getElementById('%1$s').dataset.iiifUrl,
                    thumbnailNavigationPosition: 'far-bottom'
                }
            ]
        });
        </script>
        EOD;
        return sprintf($html, sprintf('media-%s', $media->id()), $view->escapeHtml($media->source()));
    }
}
