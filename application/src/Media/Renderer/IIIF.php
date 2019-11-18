<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class IIIF implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $IIIFData = $media->mediaData();
        $view->headScript()
            ->appendFile($view->assetUrl('vendor/openseadragon/openseadragon.min.js', 'Omeka'), 'text/javascript', ['defer' => 'defer']);
        $prefixUrl = $view->assetUrl('vendor/openseadragon/images/', 'Omeka', false, false);
        $noscript = $view->translate('OpenSeadragon is not available unless JavaScript is enabled.');
        $image =
            '<div class="openseadragon" id="iiif-' . $media->id() . '" style="height: 400px;"></div>
<script type="text/javascript">
window.addEventListener("DOMContentLoaded", function() {
    (function($) {
        var viewer = OpenSeadragon({
            id: "iiif-' . $media->id() . '",
            prefixUrl: "' . $prefixUrl . '",
            tileSources: [
                ' . json_encode($IIIFData) . '
            ]
        });
    })(jQuery);
});
</script>
<noscript>
    <p>' . $noscript . '</p>
</noscript>
';
        return $image;
    }
}
