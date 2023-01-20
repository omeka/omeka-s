<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class LightboxGallery implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Lightbox gallery'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'media'];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        if ($resource instanceof ItemRepresentation) {
            return $view->partial('common/resource-page-block-layout/lightbox-gallery-item', ['resource' => $resource]);
        } elseif ($resource instanceof MediaRepresentation) {
            return $view->partial('common/resource-page-block-layout/lightbox-gallery-media', ['resource' => $resource]);
        }
    }
}
