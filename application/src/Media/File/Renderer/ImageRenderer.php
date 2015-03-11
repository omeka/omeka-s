<?php
namespace Omeka\Media\File\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Renderer\PhpRenderer;

class ImageRenderer implements RendererInterface
{
    use ServiceLocatorAwareTrait;

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ){
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        $url = $fileStore->getUri($media->filename());

        return sprintf('<img src="%s">', $view->escapeHtmlAttr($url));
    }
}
