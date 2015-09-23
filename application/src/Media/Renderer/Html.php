<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class Html extends AbstractRenderer
{
    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        $data = $media->mediaData();
        return $data['html'];
    }
}

