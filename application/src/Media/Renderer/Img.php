<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

class Img implements RendererInterface
{
    const WIDTH = 420;
    const HEIGHT = 315;
    const ALT = '';

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {}

    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
    {
        $options = $this->sanitizeOptions($options);
        $data = $media->getData();
        $embed = '<img'
               . ' width="' . $options['width'] . '"'
               . ' height="' . $options['height'] . '"'
               . ' src="' . $data['src'] . '"'
               . ' alt="' . $data['alt'] . '"'
               . ' />';
        return $embed;
    }

    protected function sanitizeOptions(array $options)
    {
        if (!isset($options['width'])) {
            $options['width'] = self::WIDTH;
        }
        if (!isset($options['height'])) {
            $options['height'] = self::HEIGHT;
        }
        if (!isset($options['alt'])) {
            $options['alt'] = self::ALT;
        }
        return $options;
    }
}
