<?php
namespace Omeka\View\Helper\MediaType;

use Omeka\Model\Entity\Media;

interface MediaTypeInterface
{
    /**
     * Return the HTML necessary to render an add/edit form.
     *
     * @param array|null $options
     * @param Media|null $media
     * @return string
     */
    public function form(array $options = array(), Media $media = null);

    /**
     * Return the HTML necessary to render the provided media.
     *
     * @param Media $media
     * @param array|null $options
     * @return string
     */
    public function render(Media $media, array $options = array());
}
