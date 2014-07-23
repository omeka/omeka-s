<?php
namespace Omeka\View\Helper\MediaType;

use Omeka\Api\Representation\Entity\MediaRepresentation;

class Img implements MediaTypeInterface
{
    const WIDTH = 420;
    const HEIGHT = 315;
    const ALT = '';

    /**
     * {@inheritDoc}
     */
    public function form(MediaRepresentation $media = null, array $options = array())
    {}

    /**
     * {@inheritDoc}
     */
    public function render(MediaRepresentation $media, array $options = array())
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
        return $options
    }
}
