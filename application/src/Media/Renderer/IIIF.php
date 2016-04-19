<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

class IIIF implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $IIIFData = $media->mediaData();
        $view->headScript()->appendFile($view->assetUrl('js/openseadragon/openseadragon.min.js', 'Omeka'));
        $prefixUrl = $view->assetUrl('js/openseadragon/images/', 'Omeka');
        $image =
            '<div class="openseadragon" id="iiif-'.$media->id().'" style="width: 600px; height: 400px;"></div>
            <script type="text/javascript">
                var viewer = OpenSeadragon({
                    id: "iiif-'.$media->id().'",
                    prefixUrl: "'. $prefixUrl . '",
                    tileSources: [
                        '. json_encode($IIIFData) .'
                    ]
                });
            </script>'
        ;
        return $image;
    }
}
