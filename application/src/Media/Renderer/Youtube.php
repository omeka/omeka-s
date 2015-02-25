<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;

class Youtube implements RendererInterface
{
    const WIDTH = 420;
    const HEIGHT = 315;
    const ALLOWFULLSCREEN = true;

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
        $data = $media->getMediaData();
        $embed = '<iframe'
               . ' width="' . $options['width'] . '"'
               . ' height="' . $options['height'] . '"'
               . ' src="' . $data['src'] . '"'
               . ' frameborder="0"';
        if ($options['allowfullscreen']) {
            $embed .= ' allowfullscreen';
        }
        $embed .= '></iframe>';
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
        if (!isset($options['allowfullscreen'])) {
            $options['allowfullscreen'] = self::ALLOWFULLSCREEN;
        }
        return $options;
    }
}
