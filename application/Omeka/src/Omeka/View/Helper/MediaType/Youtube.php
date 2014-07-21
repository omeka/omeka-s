<?php
namespace Omeka\View\Helper\MediaType;

class Youtube implements MediaTypeInterface
{
    const WIDTH = 420;
    const HEIGHT = 315;
    const ALLOWFULLSCREEN = true;

    /**
     * {@inheritDoc}
     */
    public function form(array $options = array(), Media $media = null)
    {}

    /**
     * {@inheritDoc}
     */
    public function render(Media $media, array $options = array())
    {
        $options = $this->sanitizeOptions($options);
        $data = $media->getData();
        $embed = '<iframe'
               . ' width="' . $options['width'] . '"'
               . ' height="' . $options['height'] . '"'
               . ' src="' . $data['url'] . '"'
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
        return $options
    }
}
